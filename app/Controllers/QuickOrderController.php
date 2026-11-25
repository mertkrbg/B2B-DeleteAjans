<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\BulkOrderParser;
use App\Services\CartService;
use App\Services\PricingEngine;

/**
 * Class QuickOrderController
 *
 * B2B Hızlı Sipariş Matrisi ve Toplu Yükleme Kontrolcüsü.
 * Çok satırlı canlı SKU arama matrisi, Excel/CSV dosya yükleme
 * ve serbest metin kopyala-yapıştır ile anında sepete ürün aktarımını yönetir.
 *
 * @package App\Controllers
 */
class QuickOrderController extends PortalController
{
    public function index(Request $request): Response
    {
        $bulkResult = Session::pull('bulk_result');
        return $this->renderPortal('quick_order', [
            'title'      => 'Hızlı Sipariş',
            'bulkResult' => $bulkResult,
            'rowCount'   => 15,
        ]);
    }

    public function search(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return Response::json(['products' => []]);
        }
        $like = '%' . $q . '%';
        $rows = Database::fetchAll(
            'SELECT p.id, p.sku, p.name, p.unit_type, p.min_order_qty, p.order_step_qty,
                    p.base_list_price, p.currency, p.vat_rate,
                    (SELECT COALESCE(SUM(ps.physical_qty - ps.reserved_qty), 0) FROM product_stocks ps WHERE ps.product_id = p.id) AS stock
             FROM products p
             WHERE p.is_active = 1 AND (p.sku LIKE :q1 OR p.name LIKE :q2 OR p.barcode LIKE :q3 OR p.oem_code LIKE :q4)
             ORDER BY p.is_featured DESC, p.sku ASC
             LIMIT 10',
            [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like]
        );

        $engine = PricingEngine::forCompany($this->companyId());
        $products = [];
        foreach ($rows as $row) {
            $price = $engine->price($row, (int) $row['min_order_qty']);
            $products[] = [
                'id'     => (int) $row['id'],
                'sku'    => (string) $row['sku'],
                'name'   => (string) $row['name'],
                'unit'   => (string) $row['unit_type'],
                'price'  => $price['unit_net'],
                'price_formatted' => money($price['unit_net']),
                'stock'  => (int) $row['stock'],
                'min'    => (int) $row['min_order_qty'],
                'step'   => (int) $row['order_step_qty'],
            ];
        }
        return Response::json(['products' => $products]);
    }

    public function price(Request $request): Response
    {
        $productId = $request->int('product_id');
        $qty = $request->int('quantity');
        if ($productId <= 0 || $qty <= 0) {
            return Response::json(['ok' => false, 'message' => 'Geçersiz ürün veya adet.'], 422);
        }
        $product = CartService::product((string) $productId);
        if ($product === null) {
            return Response::json(['ok' => false, 'message' => 'Ürün bulunamadı.'], 404);
        }
        $engine = PricingEngine::forCompany($this->companyId());
        $price = $engine->price($product, $qty);
        $lineTotal = round($price['unit_net'] * $qty, 4);
        return Response::json([
            'ok'                   => true,
            'unit_net'             => $price['unit_net'],
            'price_formatted'      => money($price['unit_net']),
            'tier_discount'        => $price['tier_discount'],
            'line_total'           => $lineTotal,
            'line_total_formatted' => money($lineTotal),
        ]);
    }

    public function transfer(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $productIds = (array) $request->input('product_id', []);
        $quantities = (array) $request->input('quantity', []);
        $items = [];
        foreach ($productIds as $i => $pid) {
            $pid = (int) $pid;
            $qty = (int) ($quantities[$i] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $items[] = ['product_id' => $pid, 'quantity' => $qty];
            }
        }
        if (empty($items)) {
            Session::flash('warning', 'Sepete aktarılacak geçerli ürün satırı bulunamadı.');
            return Response::redirect(url('portal/hizli-siparis'));
        }
        $result = CartService::addMany($this->companyId(), (int) Session::authId(), $items);
        if ($result['added'] > 0) {
            Session::flash('success', $result['added'] . ' ürün sepete aktarıldı.');
        }
        foreach ($result['errors'] as $error) {
            Session::flash('error', $error);
        }
        return Response::redirect(url($result['added'] > 0 ? 'portal/sepet' : 'portal/hizli-siparis'));
    }

    public function bulk(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $rows = [];
        $file = $request->file('bulk_file');
        if ($file !== null) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                Session::flash('error', 'CSV dosyası yüklenemedi.');
            } else {
                $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['csv', 'txt'], true)) {
                    Session::flash('error', 'Yalnızca .csv veya .txt dosyası yükleyebilirsiniz.');
                } else {
                    $rows = array_merge($rows, BulkOrderParser::parseFile($file['tmp_name']));
                }
            }
        }
        $text = $request->string('bulk_text');
        if ($text !== '') {
            $rows = array_merge($rows, BulkOrderParser::parseText($text));
        }
        if (empty($rows)) {
            Session::flash('warning', 'Ayrıştırılacak geçerli satır bulunamadı. Format: SKU;ADET');
            return Response::redirect(url('portal/hizli-siparis'));
        }

        $resolved = BulkOrderParser::resolve($rows, $this->companyId());
        $added = 0;
        $cartErrors = [];
        $validRows = [];
        foreach ($resolved['valid'] as $row) {
            $add = CartService::addItem($this->companyId(), (int) Session::authId(), (int) $row['product_id'], (int) $row['quantity']);
            if ($add['ok']) {
                $added++;
                $validRows[] = $row;
            } else {
                $cartErrors[] = $add['message'];
            }
        }

        $result = [
            'valid'  => $validRows,
            'errors' => array_merge($resolved['errors'], $cartErrors),
            'added'  => $added,
        ];
        Session::set('bulk_result', $result);
        if ($added > 0) {
            Session::flash('success', $added . ' ürün toplu yükleme ile sepete eklendi.');
        }
        if (!empty($result['errors'])) {
            Session::flash('warning', count($result['errors']) . ' satırda hata tespit edildi, detaylar aşağıda listelendi.');
        }
        return Response::redirect(url('portal/hizli-siparis'));
    }

    public function template(Request $request): Response
    {
        return Response::download(BulkOrderParser::templateCsv(), 'b2b-hizli-siparis-sablonu.csv');
    }
}
