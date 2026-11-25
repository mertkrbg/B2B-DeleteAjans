<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CartService;
use App\Services\CreditRiskService;
use App\Services\InvoicePdfService;

/**
 * Class OrderController
 *
 * B2B Sipariş Takip ve Onay Kontrolcüsü.
 * Sipariş geçmişi listeleme, sipariş detay dökümü, alt kullanıcı siparişlerini
 * limit bazlı onaylama/reddetme ve yazdırılabilir proforma fatura çıktısını yönetir.
 *
 * @package App\Controllers
 */
class OrderController extends PortalController
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->query('durum', ''));
        $where = 'company_id = :co';
        $params = [':co' => $this->companyId()];
        if ($status !== '') {
            $where .= ' AND status = :st';
            $params[':st'] = $status;
        }
        $orders = Database::fetchAll(
            "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o WHERE $where ORDER BY o.created_at DESC, o.id DESC",
            $params
        );
        $countsRaw = Database::fetchAll(
            'SELECT status, COUNT(*) AS c FROM orders WHERE company_id = :co GROUP BY status',
            [':co' => $this->companyId()]
        );
        $counts = [];
        foreach ($countsRaw as $row) {
            $counts[$row['status']] = (int) $row['c'];
        }

        return $this->renderPortal('orders', [
            'title'        => 'Siparişlerim',
            'orders'       => $orders,
            'statusFilter' => $status,
            'counts'       => $counts,
        ]);
    }

    public function show(Request $request, string $orderNumber): Response
    {
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            return $this->notFound();
        }
        $items = Database::fetchAll('SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC', [':id' => $order['id']]);
        $history = Database::fetchAll(
            'SELECT h.*, u.full_name FROM order_status_history h
             LEFT JOIN users u ON u.id = h.user_id
             WHERE h.order_id = :id ORDER BY h.created_at ASC, h.id ASC',
            [':id' => $order['id']]
        );
        $shipping = Database::fetch('SELECT * FROM company_addresses WHERE id = :id', [':id' => $order['shipping_address_id']]);
        $billing = Database::fetch('SELECT * FROM company_addresses WHERE id = :id', [':id' => $order['billing_address_id']]);

        return $this->renderPortal('order_detail', [
            'title'    => 'Sipariş ' . $order['order_number'],
            'order'    => $order,
            'items'    => $items,
            'history'  => $history,
            'shipping' => $shipping,
            'billing'  => $billing,
        ]);
    }

    public function reorder(Request $request, string $orderNumber): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            return $this->notFound();
        }
        $rows = Database::fetchAll('SELECT product_id, quantity, warehouse_id FROM order_items WHERE order_id = :id', [':id' => $order['id']]);
        $items = [];
        foreach ($rows as $row) {
            $items[] = [
                'product_id'   => (int) $row['product_id'],
                'quantity'     => (int) $row['quantity'],
                'warehouse_id' => $row['warehouse_id'] !== null ? (int) $row['warehouse_id'] : null,
            ];
        }
        $result = CartService::addMany($this->companyId(), (int) Session::authId(), $items);
        if ($result['added'] > 0) {
            Session::flash('success', $result['added'] . ' kalem sepete aktarıldı.');
        }
        foreach ($result['errors'] as $error) {
            Session::flash('error', $error);
        }
        if ($result['added'] === 0) {
            Session::flash('warning', 'Sipariş kalemleri sepete aktarılamadı (stok veya ürün durumu).');
            return Response::redirect(url('portal/siparis/' . $order['order_number']));
        }
        return Response::redirect(url('portal/sepet'));
    }

    public function approve(Request $request, string $orderNumber): Response
    {
        if (!$this->canApproveOrders()) {
            Session::flash('error', 'Sipariş onaylama yetkiniz bulunmuyor.');
            return Response::redirect(url('portal/siparis/' . $orderNumber));
        }
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            return $this->notFound();
        }
        if ($order['status'] !== 'pending_company_approval') {
            Session::flash('warning', 'Bu sipariş onay beklemiyor.');
            return Response::redirect(url('portal/siparis/' . $order['order_number']));
        }
        $risk = CreditRiskService::snapshot($this->companyId());
        $newStatus = 'approved';
        if ($order['payment_method'] === 'open_account' && ($risk['has_overdue'] || bccomp((string) $order['grand_total'], (string) $risk['available'], 2) === 1)) {
            $newStatus = 'pending_admin_approval';
        }
        try {
            Database::transaction(function () use ($order, $newStatus): void {
                Database::update('orders', [
                    'status' => $newStatus,
                    'approved_by_user_id' => Session::authId(),
                    'updated_at' => date('Y-m-d H:i:s'),
                ], 'id = :id', [':id' => $order['id']]);
                Database::insert('order_status_history', [
                    'order_id' => $order['id'], 'user_id' => Session::authId(),
                    'old_status' => 'pending_company_approval', 'new_status' => $newStatus,
                    'comment' => 'Firma içi onay verildi.', 'created_at' => date('Y-m-d H:i:s'),
                ]);
                if ($newStatus === 'approved') {
                    foreach (Database::fetchAll('SELECT product_id, quantity, warehouse_id FROM order_items WHERE order_id = :id', [':id' => $order['id']]) as $item) {
                        if ($item['warehouse_id'] !== null) {
                            Database::query(
                                'UPDATE product_stocks SET reserved_qty = reserved_qty + :q WHERE product_id = :p AND warehouse_id = :w',
                                [':q' => (int) $item['quantity'], ':p' => (int) $item['product_id'], ':w' => (int) $item['warehouse_id']]
                            );
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            Session::flash('error', 'Sipariş onaylanırken bir hata oluştu.');
            return Response::redirect(url('portal/siparis/' . $order['order_number']));
        }
        Session::flash('success', $newStatus === 'approved' ? 'Sipariş onaylandı.' : 'Sipariş onaylandı, risk limiti nedeniyle finans onayına gönderildi.');
        return Response::redirect(url('portal/siparis/' . $order['order_number']));
    }

    public function reject(Request $request, string $orderNumber): Response
    {
        if (!$this->canApproveOrders()) {
            Session::flash('error', 'Sipariş reddetme yetkiniz bulunmuyor.');
            return Response::redirect(url('portal/siparis/' . $orderNumber));
        }
        $order = $this->findOrder($orderNumber);
        if ($order === null) {
            return $this->notFound();
        }
        if ($order['status'] !== 'pending_company_approval') {
            Session::flash('warning', 'Bu sipariş onay beklemiyor.');
            return Response::redirect(url('portal/siparis/' . $order['order_number']));
        }
        Database::update('orders', ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $order['id']]);
        Database::insert('order_status_history', [
            'order_id' => $order['id'], 'user_id' => Session::authId(),
            'old_status' => 'pending_company_approval', 'new_status' => 'rejected',
            'comment' => $request->string('reason') !== '' ? $request->string('reason') : 'Firma içi onay reddedildi.',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Sipariş reddedildi.');
        return Response::redirect(url('portal/siparis/' . $order['order_number']));
    }

    public function printOrder(Request $request, string $orderNumber): Response
    {
        $data = InvoicePdfService::orderDocument($this->companyId(), $orderNumber);
        if ($data === null) {
            return $this->notFound();
        }
        $html = View::render('print.order', array_merge(['title' => 'Proforma ' . $orderNumber], $data), 'print');
        return Response::html($html);
    }

    private function canApproveOrders(): bool
    {
        return in_array((string) Session::get('role', ''), ['company_owner', 'purchasing_manager'], true);
    }

    private function findOrder(string $orderNumber): ?array
    {
        return Database::fetch(
            'SELECT * FROM orders WHERE order_number = :n AND company_id = :co LIMIT 1',
            [':n' => $orderNumber, ':co' => $this->companyId()]
        );
    }

    private function notFound(): Response
    {
        return Response::html(View::render('errors.404', ['title' => 'Sipariş Bulunamadı'], 'auth'), 404);
    }
}
