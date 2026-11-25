<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Class AdminOrderController
 *
 * ERP Sipariş Operasyonları Kontrolcüsü.
 * Sipariş onaylama, stok rezervasyonu, sevkiyat (kargo firması ve takip numarası girişi),
 * faturalandırma ve cari hesaba borç tahakkukunu yönetir.
 *
 * @package App\Controllers\Admin
 */
class AdminOrderController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->query('durum', ''));
        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND o.status = :st';
            $params[':st'] = $status;
        }
        $orders = Database::fetchAll(
            "SELECT o.*, c.title AS company_title,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o JOIN companies c ON c.id = o.company_id
             WHERE $where ORDER BY o.created_at DESC, o.id DESC LIMIT 200",
            $params
        );
        $countsRaw = Database::fetchAll('SELECT status, COUNT(*) AS c FROM orders GROUP BY status');
        $counts = ['all' => 0];
        foreach ($countsRaw as $r) {
            $counts[$r['status']] = (int) $r['c'];
            $counts['all'] += (int) $r['c'];
        }
        return $this->renderAdmin('orders', [
            'title' => 'Sipariş Yönetimi',
            'orders' => $orders,
            'statusFilter' => $status,
            'counts' => $counts,
        ]);
    }

    public function show(Request $request, string $orderNumber): Response
    {
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            return Response::html(View::render('errors.404', ['title' => 'Sipariş Bulunamadı'], 'auth'), 404);
        }
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id', [':id' => $order['company_id']]);
        $items = Database::fetchAll('SELECT * FROM order_items WHERE order_id = :id ORDER BY id', [':id' => $order['id']]);
        $history = Database::fetchAll('SELECT h.*, u.full_name FROM order_status_history h LEFT JOIN users u ON u.id = h.user_id WHERE h.order_id = :id ORDER BY h.created_at ASC, h.id ASC', [':id' => $order['id']]);
        $shipping = Database::fetch('SELECT * FROM company_addresses WHERE id = :id', [':id' => $order['shipping_address_id']]);
        $billing = Database::fetch('SELECT * FROM company_addresses WHERE id = :id', [':id' => $order['billing_address_id']]);
        return $this->renderAdmin('order_detail', [
            'title' => 'Sipariş ' . $order['order_number'],
            'order' => $order,
            'company' => $company,
            'items' => $items,
            'history' => $history,
            'shipping' => $shipping,
            'billing' => $billing,
        ]);
    }

    public function approve(Request $request, string $orderNumber): Response
    {
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            Session::flash('error', 'Sipariş bulunamadı.');
            return Response::redirect(url('admin/siparisler'));
        }
        if (!in_array($order['status'], ['pending_admin_approval', 'pending_company_approval'], true)) {
            Session::flash('warning', 'Bu sipariş onay beklemiyor.');
            return Response::redirect(url('admin/siparisler/' . $order['order_number']));
        }
        try {
            Database::transaction(function () use ($order): void {
                Database::update('orders', ['status' => 'approved', 'approved_by_user_id' => Session::authId(), 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $order['id']]);
                Database::insert('order_status_history', ['order_id' => $order['id'], 'user_id' => Session::authId(), 'old_status' => $order['status'], 'new_status' => 'approved', 'comment' => 'Yönetim tarafından onaylandı.', 'created_at' => date('Y-m-d H:i:s')]);
                foreach (Database::fetchAll('SELECT product_id, quantity, warehouse_id FROM order_items WHERE order_id = :id', [':id' => $order['id']]) as $item) {
                    if ($item['warehouse_id'] !== null) {
                        Database::query('UPDATE product_stocks SET reserved_qty = reserved_qty + :q WHERE product_id = :p AND warehouse_id = :w', [':q' => (int) $item['quantity'], ':p' => (int) $item['product_id'], ':w' => (int) $item['warehouse_id']]);
                    }
                }
            });
        } catch (\Throwable $e) {
            Session::flash('error', 'Onay sırasında hata oluştu.');
            return Response::redirect(url('admin/siparisler/' . $order['order_number']));
        }
        Session::flash('success', 'Sipariş onaylandı.');
        return Response::redirect(url('admin/siparisler/' . $order['order_number']));
    }

    public function ship(Request $request, string $orderNumber): Response
    {
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            Session::flash('error', 'Sipariş bulunamadı.');
            return Response::redirect(url('admin/siparisler'));
        }
        $cargo = $request->string('cargo_company');
        $tracking = $request->string('tracking_number');
        if ($cargo === '' || $tracking === '') {
            Session::flash('error', 'Kargo firması ve takip numarası zorunludur.');
            return Response::redirect(url('admin/siparisler/' . $order['order_number']));
        }
        $newStatus = in_array($order['status'], ['delivered', 'cancelled', 'rejected'], true) ? $order['status'] : 'shipped';
        Database::update('orders', [
            'cargo_company' => $cargo, 'tracking_number' => $tracking, 'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $order['id']]);
        Database::insert('order_status_history', ['order_id' => $order['id'], 'user_id' => Session::authId(), 'old_status' => $order['status'], 'new_status' => $newStatus, 'comment' => 'Kargo: ' . $cargo . ' / ' . $tracking, 'created_at' => date('Y-m-d H:i:s')]);
        Session::flash('success', 'Sipariş sevk edildi olarak işaretlendi.');
        return Response::redirect(url('admin/siparisler/' . $order['order_number']));
    }

    public function invoice(Request $request, string $orderNumber): Response
    {
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            Session::flash('error', 'Sipariş bulunamadı.');
            return Response::redirect(url('admin/siparisler'));
        }
        if (!empty($order['invoice_number'])) {
            Session::flash('warning', 'Bu sipariş için fatura zaten kesilmiş.');
            return Response::redirect(url('admin/siparisler/' . $order['order_number']));
        }
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id', [':id' => $order['company_id']]);
        if ($company === null) {
            Session::flash('error', 'Bayi bulunamadı.');
            return Response::redirect(url('admin/siparisler/' . $order['order_number']));
        }
        $year = date('Y');
        $seq = (int) Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE invoice_number LIKE :p", [':p' => 'FTR-' . $year . '-%']) + 1;
        do {
            $invoiceNumber = sprintf('FTR-%s-%04d', $year, $seq);
            $exists = Database::fetchColumn('SELECT COUNT(*) FROM orders WHERE invoice_number = :n', [':n' => $invoiceNumber]);
            $seq++;
        } while ((int) $exists > 0);

        try {
            Database::transaction(function () use ($order, $company, $invoiceNumber): void {
                $grand = (string) $order['grand_total'];
                $newBalance = bcadd((string) $company['current_balance'], $grand, 4);
                $dueDate = date('Y-m-d', strtotime('+' . (int) $order['payment_term_days'] . ' days'));
                Database::insert('current_account_transactions', [
                    'company_id' => (int) $order['company_id'],
                    'transaction_no' => 'CT-' . date('Y') . '-' . str_pad((string) ((int) Database::fetchColumn('SELECT COUNT(*) FROM current_account_transactions') + 1), 5, '0', STR_PAD_LEFT),
                    'type' => 'invoice_debit', 'reference_type' => 'order', 'reference_id' => (int) $order['id'],
                    'description' => $invoiceNumber . ' Nolu Sipariş Faturası (' . $order['order_number'] . ')',
                    'debit_amount' => $grand, 'credit_amount' => 0, 'running_balance' => $newBalance,
                    'due_date' => $dueDate, 'status' => 'confirmed', 'created_by' => Session::authId(),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                Database::update('companies', ['current_balance' => $newBalance, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $order['company_id']]);
                $newStatus = in_array($order['status'], ['delivered', 'cancelled', 'rejected'], true) ? $order['status'] : 'shipped';
                Database::update('orders', ['invoice_number' => $invoiceNumber, 'status' => $newStatus, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $order['id']]);
                Database::insert('order_status_history', ['order_id' => $order['id'], 'user_id' => Session::authId(), 'old_status' => $order['status'], 'new_status' => $newStatus, 'comment' => 'Fatura kesildi: ' . $invoiceNumber, 'created_at' => date('Y-m-d H:i:s')]);
                Database::insert('audit_logs', [
                    'user_id' => Session::authId(), 'company_id' => (int) $order['company_id'], 'action' => 'order.invoice',
                    'entity_type' => 'order', 'entity_id' => (int) $order['id'],
                    'new_values_json' => json_encode(['invoice_number' => $invoiceNumber, 'grand_total' => $grand], JSON_UNESCAPED_UNICODE),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'created_at' => date('Y-m-d H:i:s'),
                ]);
            });
        } catch (\Throwable $e) {
            Session::flash('error', 'Fatura kesilirken hata oluştu.');
            return Response::redirect(url('admin/siparisler/' . $order['order_number']));
        }
        Session::flash('success', 'Fatura kesildi ve cari hesaba borç işlendi. Fatura No: ' . $invoiceNumber);
        return Response::redirect(url('admin/siparisler/' . $order['order_number']));
    }

    private function findOrder(string $orderNumber): ?array
    {
        return Database::fetch('SELECT * FROM orders WHERE order_number = :n LIMIT 1', [':n' => $orderNumber]);
    }
}
