<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

/**
 * Class OrderService
 *
 * Sipariş Oluşturma ve Doğrulama Servisi.
 * Alt kullanıcı sipariş onay limitleri, cari risk limiti kontrolleri,
 * stok rezervasyonları ve ödeme türlerine göre sipariş yaşam döngüsünü yönetir.
 *
 * @package App\Services
 */
class OrderService
{
    private const PAYMENT_METHODS = ['open_account', 'bank_transfer', 'credit_card', 'cheque'];

    public static function createFromCart(int $companyId, int $userId, array $input): array
    {
        $items = $input['items'] ?? [];
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Sepetinizde ürün bulunmuyor.'];
        }
        $paymentMethod = (string) ($input['payment_method'] ?? '');
        if (!in_array($paymentMethod, self::PAYMENT_METHODS, true)) {
            return ['ok' => false, 'message' => 'Geçersiz ödeme yöntemi.'];
        }

        $shippingAddressId = (int) ($input['shipping_address_id'] ?? 0);
        $billingAddressId = (int) ($input['billing_address_id'] ?? 0);
        $shipping = Database::fetch('SELECT id FROM company_addresses WHERE id = :id AND company_id = :co LIMIT 1', [':id' => $shippingAddressId, ':co' => $companyId]);
        $billing = Database::fetch('SELECT id FROM company_addresses WHERE id = :id AND company_id = :co LIMIT 1', [':id' => $billingAddressId, ':co' => $companyId]);
        if ($shipping === null || $billing === null) {
            return ['ok' => false, 'message' => 'Geçerli sevkiyat ve fatura adresi seçiniz.'];
        }

        $company = Database::fetch('SELECT payment_term_days FROM companies WHERE id = :id', [':id' => $companyId]);
        $user = Database::fetch('SELECT role, order_approval_limit FROM users WHERE id = :id', [':id' => $userId]);

        $totals = CartService::totals($items);
        $subtotalNet = $totals['net'];
        $totalVat = $totals['vat'];
        $grandTotal = $totals['gross'];

        $subtotalList = 0.0;
        foreach ($items as $item) {
            $subtotalList += (float) $item['unit_list_price'] * (int) $item['quantity'];
        }
        $subtotalList = round($subtotalList, 2);
        $totalDiscount = round($subtotalList - $subtotalNet, 2);

        $snapshot = CreditRiskService::snapshot($companyId);
        $userLimit = $user['order_approval_limit'] !== null ? (string) $user['order_approval_limit'] : null;

        $status = 'approved';
        if ($userLimit !== null && bccomp((string) $grandTotal, $userLimit, 2) === 1) {
            $status = 'pending_company_approval';
        } elseif ($paymentMethod === 'open_account') {
            if ($snapshot['has_overdue'] || bccomp((string) $grandTotal, (string) $snapshot['available'], 2) === 1) {
                $status = 'pending_admin_approval';
            }
        }

        $termDays = $paymentMethod === 'open_account' ? (int) ($company['payment_term_days'] ?? 0) : 0;

        try {
            $result = Database::transaction(function () use (
                $companyId, $userId, $items, $shippingAddressId, $billingAddressId, $paymentMethod, $termDays,
                $status, $subtotalList, $totalDiscount, $subtotalNet, $totalVat, $grandTotal, $input
            ): array {
                $year = date('Y');
                $seq = (int) Database::fetchColumn(
                    "SELECT COUNT(*) FROM orders WHERE order_number LIKE :p",
                    [':p' => 'SIP-' . $year . '-%']
                ) + 1;
                do {
                    $orderNumber = sprintf('SIP-%s-%05d', $year, $seq);
                    $exists = Database::fetchColumn('SELECT COUNT(*) FROM orders WHERE order_number = :n', [':n' => $orderNumber]);
                    $seq++;
                } while ((int) $exists > 0);

                $orderId = Database::insert('orders', [
                    'order_number'         => $orderNumber,
                    'company_id'           => $companyId,
                    'created_by_user_id'   => $userId,
                    'shipping_address_id'  => $shippingAddressId,
                    'billing_address_id'   => $billingAddressId,
                    'customer_po_number'   => ($input['customer_po_number'] ?? '') !== '' ? $input['customer_po_number'] : null,
                    'payment_method'       => $paymentMethod,
                    'payment_term_days'    => $termDays,
                    'status'               => $status,
                    'subtotal_list'        => round($subtotalList, 4),
                    'total_discount'       => round($totalDiscount, 4),
                    'subtotal_net'         => round($subtotalNet, 4),
                    'shipping_cost'        => 0,
                    'total_vat'            => round($totalVat, 4),
                    'grand_total'          => round($grandTotal, 4),
                    'customer_note'        => ($input['customer_note'] ?? '') !== '' ? $input['customer_note'] : null,
                    'created_at'           => date('Y-m-d H:i:s'),
                    'updated_at'           => date('Y-m-d H:i:s'),
                ]);

                foreach ($items as $item) {
                    $qty = (int) $item['quantity'];
                    $unitNet = round((float) $item['unit_net_price'], 2);
                    $lineNet = round((float) $item['line_total_net'], 2);
                    $lineVat = round($lineNet * (float) $item['vat_rate'] / 100, 2);
                    Database::insert('order_items', [
                        'order_id'        => $orderId,
                        'product_id'      => (int) $item['product_id'],
                        'warehouse_id'    => $item['warehouse_id'] !== null ? (int) $item['warehouse_id'] : null,
                        'sku_snapshot'    => (string) $item['sku'],
                        'name_snapshot'   => (string) $item['name'],
                        'quantity'        => $qty,
                        'shipped_quantity'=> 0,
                        'unit_list_price' => (float) $item['unit_list_price'],
                        'discount_rate'   => (float) $item['applied_discount_rate'],
                        'unit_net_price'  => $unitNet,
                        'vat_rate'        => (float) $item['vat_rate'],
                        'line_vat'        => $lineVat,
                        'line_total'      => $lineNet,
                    ]);

                    if ($status === 'approved' && $item['warehouse_id'] !== null) {
                        Database::query(
                            'UPDATE product_stocks SET reserved_qty = reserved_qty + :q WHERE product_id = :p AND warehouse_id = :w',
                            [':q' => $qty, ':p' => (int) $item['product_id'], ':w' => (int) $item['warehouse_id']]
                        );
                    }
                }

                Database::insert('order_status_history', [
                    'order_id'  => $orderId,
                    'user_id'   => $userId,
                    'old_status'=> null,
                    'new_status'=> $status,
                    'comment'   => 'Sipariş oluşturuldu.',
                    'created_at'=> date('Y-m-d H:i:s'),
                ]);

                $cartId = CartService::activeCart($companyId, $userId);
                Database::delete('cart_items', 'cart_id = :c', [':c' => $cartId]);
                Database::update('carts', ['po_number' => null, 'notes' => null], 'id = :id', [':id' => $cartId]);

                Database::insert('audit_logs', [
                    'user_id' => $userId, 'company_id' => $companyId, 'action' => 'order.create',
                    'entity_type' => 'order', 'entity_id' => $orderId,
                    'new_values_json' => json_encode(['order_number' => $orderNumber, 'status' => $status, 'grand_total' => $grandTotal], JSON_UNESCAPED_UNICODE),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);

                return ['order_id' => $orderId, 'order_number' => $orderNumber];
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Sipariş oluşturulamadı. Lütfen tekrar deneyin.'];
        }

        $message = match ($status) {
            'approved'                 => 'Siparişiniz başarıyla oluşturuldu ve onaylandı.',
            'pending_company_approval' => 'Siparişiniz oluşturuldu. Harcama limitinizi aştığı için firma onayına gönderildi.',
            'pending_admin_approval'   => 'Siparişiniz oluşturuldu. Risk limiti nedeniyle finans onayı bekliyor.',
            default                    => 'Siparişiniz oluşturuldu.',
        };

        return [
            'ok' => true,
            'message' => $message,
            'order_id' => $result['order_id'],
            'order_number' => $result['order_number'],
            'status' => $status,
        ];
    }
}
