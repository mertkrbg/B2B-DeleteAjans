<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Class AdminPriceListController
 *
 * B2B Özel Fiyat Listeleri Yönetim Kontrolcüsü.
 * Fiyat listesi oluşturma, para birimi belirleme, ürün bazlı özel fiyatlar tanımlama
 * ve bayilere fiyat listesi atama işlemlerini yürütür.
 *
 * @package App\Controllers\Admin
 */
class AdminPriceListController extends AdminController
{
    public function index(Request $request): Response
    {
        $lists = Database::fetchAll(
            'SELECT pl.*,
                    (SELECT COUNT(*) FROM price_list_items pli WHERE pli.price_list_id = pl.id) AS item_count,
                    (SELECT COUNT(*) FROM companies c WHERE c.price_list_id = pl.id) AS company_count
             FROM price_lists pl ORDER BY pl.id ASC'
        );
        return $this->renderAdmin('price_lists', ['title' => 'Fiyat Listeleri', 'lists' => $lists]);
    }

    public function store(Request $request): Response
    {
        $code = strtoupper($request->string('code'));
        $name = $request->string('name');
        if ($code === '' || $name === '') {
            Session::flash('error', 'Liste kodu ve adı zorunludur.');
            return Response::redirect(url('admin/fiyat-listeleri'));
        }
        $exists = Database::fetchColumn('SELECT COUNT(*) FROM price_lists WHERE code = :c', [':c' => $code]);
        if ((int) $exists > 0) {
            Session::flash('error', 'Bu liste kodu zaten kullanılıyor.');
            return Response::redirect(url('admin/fiyat-listeleri'));
        }
        $currency = in_array($request->string('currency'), ['TRY', 'USD', 'EUR'], true) ? $request->string('currency') : 'TRY';
        Database::insert('price_lists', [
            'code' => $code,
            'name' => $name,
            'currency' => $currency,
            'discount_modifier' => (float) $request->input('discount_modifier', 0),
            'valid_from' => $request->string('valid_from') !== '' ? $request->string('valid_from') : date('Y-m-d'),
            'valid_until' => $request->string('valid_until') !== '' ? $request->string('valid_until') : null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Fiyat listesi oluşturuldu.');
        return Response::redirect(url('admin/fiyat-listeleri'));
    }

    public function show(Request $request, string $id): Response
    {
        $list = Database::fetch('SELECT * FROM price_lists WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($list === null) {
            return Response::html(View::render('errors.404', ['title' => 'Liste Bulunamadı'], 'auth'), 404);
        }
        $products = Database::fetchAll(
            'SELECT p.id, p.sku, p.name, p.base_list_price, p.currency, b.name AS brand_name,
                    pli.special_price, pli.extra_discount_rate
             FROM products p
             LEFT JOIN brands b ON b.id = p.brand_id
             LEFT JOIN price_list_items pli ON pli.product_id = p.id AND pli.price_list_id = :lid
             WHERE p.is_active = 1 ORDER BY p.sku ASC',
            [':lid' => (int) $id]
        );
        return $this->renderAdmin('price_list_detail', [
            'title' => 'Fiyat Listesi: ' . $list['name'],
            'list' => $list,
            'products' => $products,
        ]);
    }

    public function updateItems(Request $request, string $id): Response
    {
        $list = Database::fetch('SELECT * FROM price_lists WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($list === null) {
            Session::flash('error', 'Liste bulunamadı.');
            return Response::redirect(url('admin/fiyat-listeleri'));
        }
        $prices = (array) $request->input('special_price', []);
        $extras = (array) $request->input('extra_discount', []);
        $updated = 0;
        foreach ($prices as $productId => $priceRaw) {
            $productId = (int) $productId;
            $priceRaw = is_scalar($priceRaw) ? trim((string) $priceRaw) : '';
            $extra = isset($extras[$productId]) ? (float) $extras[$productId] : 0.0;
            $existing = Database::fetch(
                'SELECT id FROM price_list_items WHERE price_list_id = :l AND product_id = :p LIMIT 1',
                [':l' => (int) $id, ':p' => $productId]
            );
            if ($priceRaw === '' || (float) $priceRaw <= 0) {
                if ($existing !== null) {
                    Database::delete('price_list_items', 'id = :id', [':id' => $existing['id']]);
                    $updated++;
                }
                continue;
            }
            if ($existing !== null) {
                Database::update('price_list_items', ['special_price' => (float) $priceRaw, 'extra_discount_rate' => $extra], 'id = :id', [':id' => $existing['id']]);
            } else {
                Database::insert('price_list_items', ['price_list_id' => (int) $id, 'product_id' => $productId, 'special_price' => (float) $priceRaw, 'extra_discount_rate' => $extra]);
            }
            $updated++;
        }
        Database::insert('audit_logs', [
            'user_id' => Session::authId(), 'action' => 'price_list.update', 'entity_type' => 'price_list',
            'entity_id' => (int) $id, 'new_values_json' => json_encode(['updated_items' => $updated], JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', $updated . ' ürün fiyatı güncellendi.');
        return Response::redirect(url('admin/fiyat-listeleri/' . $id));
    }
}
