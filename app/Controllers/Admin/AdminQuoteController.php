<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Class AdminQuoteController
 *
 * ERP Teklif (RFQ) Değerlendirme Kontrolcüsü.
 * Bayilerden gelen özel fiyat taleplerini inceleme, ürün bazlı karşı teklif fiyatı verme,
 * teklif geçerlilik tarihi belirleme ve müşteriye iletme süreçlerini yönetir.
 *
 * @package App\Controllers\Admin
 */
class AdminQuoteController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->query('durum', ''));
        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND q.status = :st';
            $params[':st'] = $status;
        }
        $quotes = Database::fetchAll(
            "SELECT q.*, c.title AS company_title,
                    (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id = q.id) AS item_count
             FROM quotes q JOIN companies c ON c.id = q.company_id
             WHERE $where ORDER BY (q.status = 'requested') DESC, q.created_at DESC LIMIT 200",
            $params
        );
        return $this->renderAdmin('quotes', [
            'title' => 'Teklif Talepleri (RFQ)',
            'quotes' => $quotes,
            'statusFilter' => $status,
        ]);
    }

    public function show(Request $request, string $quoteNumber): Response
    {
        $quote = Database::fetch('SELECT * FROM quotes WHERE quote_number = :n LIMIT 1', [':n' => $quoteNumber]);
        if ($quote === null) {
            return Response::html(View::render('errors.404', ['title' => 'Teklif Bulunamadı'], 'auth'), 404);
        }
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id', [':id' => $quote['company_id']]);
        $items = Database::fetchAll(
            'SELECT qi.*, p.sku, p.name FROM quote_items qi JOIN products p ON p.id = qi.product_id WHERE qi.quote_id = :id ORDER BY qi.id',
            [':id' => $quote['id']]
        );
        return $this->renderAdmin('quote_detail', [
            'title' => 'Teklif ' . $quote['quote_number'],
            'quote' => $quote,
            'company' => $company,
            'items' => $items,
        ]);
    }

    public function offer(Request $request, string $quoteNumber): Response
    {
        $quote = Database::fetch('SELECT * FROM quotes WHERE quote_number = :n LIMIT 1', [':n' => $quoteNumber]);
        if ($quote === null) {
            Session::flash('error', 'Teklif bulunamadı.');
            return Response::redirect(url('admin/teklifler'));
        }
        if (in_array($quote['status'], ['accepted', 'rejected'], true)) {
            Session::flash('warning', 'Kabul/red edilmiş teklif güncellenemez.');
            return Response::redirect(url('admin/teklifler/' . $quote['quote_number']));
        }
        $prices = (array) $request->input('offered_price', []);
        $items = Database::fetchAll('SELECT * FROM quote_items WHERE quote_id = :id', [':id' => $quote['id']]);
        $offeredTotal = 0.0;
        $prepared = [];
        foreach ($items as $item) {
            $price = isset($prices[$item['id']]) ? (float) $prices[$item['id']] : 0.0;
            if ($price <= 0) {
                Session::flash('error', 'Tüm kalemler için geçerli bir teklif fiyatı giriniz.');
                return Response::redirect(url('admin/teklifler/' . $quote['quote_number']));
            }
            $offeredTotal += $price * (int) $item['quantity'];
            $current = (float) $item['current_net_price'];
            $prepared[] = [
                'id' => (int) $item['id'],
                'offered_price' => round($price, 4),
                'offered_discount_rate' => $current > 0 ? round((1 - $price / $current) * 100, 2) : 0.0,
            ];
        }
        $validUntil = preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->string('valid_until')) ? $request->string('valid_until') : date('Y-m-d', strtotime('+14 days'));

        try {
            Database::transaction(function () use ($quote, $prepared, $offeredTotal, $validUntil, $request): void {
                foreach ($prepared as $row) {
                    Database::update('quote_items', ['offered_price' => $row['offered_price'], 'offered_discount_rate' => $row['offered_discount_rate']], 'id = :id', [':id' => $row['id']]);
                }
                Database::update('quotes', [
                    'status' => 'offered',
                    'offered_total' => round($offeredTotal, 4),
                    'valid_until' => $validUntil,
                    'admin_note' => $request->string('admin_note') !== '' ? $request->string('admin_note') : null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = :id', [':id' => $quote['id']]);
                if (!empty($quote['user_id'])) {
                    Database::insert('notifications', [
                        'company_id' => (int) $quote['company_id'], 'user_id' => (int) $quote['user_id'],
                        'title' => 'Teklifiniz Hazır: ' . $quote['quote_number'],
                        'message' => 'Teklif talebinize özel fiyat teklifi verildi. Geçerlilik: ' . $validUntil,
                        'type' => 'info', 'is_read' => 0, 'link' => url('portal/teklif/' . $quote['quote_number']),
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                }
                Database::insert('audit_logs', [
                    'user_id' => Session::authId(), 'company_id' => (int) $quote['company_id'], 'action' => 'quote.offer',
                    'entity_type' => 'quote', 'entity_id' => (int) $quote['id'],
                    'new_values_json' => json_encode(['offered_total' => round($offeredTotal, 4), 'valid_until' => $validUntil], JSON_UNESCAPED_UNICODE),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            });
        } catch (\Throwable $e) {
            Session::flash('error', 'Teklif gönderilirken hata oluştu.');
            return Response::redirect(url('admin/teklifler/' . $quote['quote_number']));
        }
        Session::flash('success', 'Teklif fiyatlandırıldı ve bayiye gönderildi.');
        return Response::redirect(url('admin/teklifler/' . $quote['quote_number']));
    }
}
