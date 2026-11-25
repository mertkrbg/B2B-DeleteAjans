<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Class QuoteService
 *
 * Fiyat Teklifi (RFQ - Request For Quote) Yönetim Servisi.
 * Bayi özel fiyat taleplerini, karşı teklifleri, geçerlilik sürelerini
 * ve onaylanan tekliflerin siparişe dönüştürülmesini koordine eder.
 *
 * @package App\Services
 */
class QuoteService
{
    public static function create(int $companyId, int $userId, array $lines, string $note): array
    {
        $clean = [];
        foreach ($lines as $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            $qty = (int) ($line['quantity'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                continue;
            }
            $clean[] = [
                'product_id' => $productId,
                'quantity' => $qty,
                'requested_price' => isset($line['requested_price']) && $line['requested_price'] !== '' ? (float) $line['requested_price'] : null,
            ];
        }
        if (empty($clean)) {
            return ['ok' => false, 'message' => 'Teklif için en az bir geçerli ürün seçmelisiniz.'];
        }

        $engine = PricingEngine::forCompany($companyId);
        $year = date('Y');
        $seq = (int) Database::fetchColumn("SELECT COUNT(*) FROM quotes WHERE quote_number LIKE :p", [':p' => 'TKL-' . $year . '-%']) + 1;
        do {
            $quoteNumber = sprintf('TKL-%s-%04d', $year, $seq);
            $exists = Database::fetchColumn('SELECT COUNT(*) FROM quotes WHERE quote_number = :n', [':n' => $quoteNumber]);
            $seq++;
        } while ((int) $exists > 0);

        $targetTotal = 0.0;
        $prepared = [];
        foreach ($clean as $line) {
            $product = Database::fetch('SELECT * FROM products WHERE id = :id AND is_active = 1', [':id' => $line['product_id']]);
            if ($product === null) {
                continue;
            }
            $price = $engine->price($product, $line['quantity']);
            if ($line['requested_price'] !== null) {
                $targetTotal += $line['requested_price'] * $line['quantity'];
            }
            $prepared[] = [
                'product_id' => (int) $product['id'],
                'quantity' => $line['quantity'],
                'current_net_price' => $price['unit_net'],
                'requested_price' => $line['requested_price'],
            ];
        }
        if (empty($prepared)) {
            return ['ok' => false, 'message' => 'Geçerli ürün bulunamadı.'];
        }

        try {
            $quoteId = Database::transaction(function () use ($companyId, $userId, $quoteNumber, $note, $prepared, $targetTotal): int {
                $quoteId = Database::insert('quotes', [
                    'quote_number' => $quoteNumber,
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'status' => 'requested',
                    'valid_until' => date('Y-m-d', strtotime('+14 days')),
                    'target_total' => $targetTotal > 0 ? round($targetTotal, 4) : null,
                    'customer_note' => $note !== '' ? $note : null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                foreach ($prepared as $row) {
                    Database::insert('quote_items', [
                        'quote_id' => $quoteId,
                        'product_id' => $row['product_id'],
                        'quantity' => $row['quantity'],
                        'current_net_price' => $row['current_net_price'],
                        'requested_price' => $row['requested_price'],
                    ]);
                }
                Database::insert('audit_logs', [
                    'user_id' => $userId, 'company_id' => $companyId, 'action' => 'quote.create',
                    'entity_type' => 'quote', 'entity_id' => $quoteId,
                    'new_values_json' => json_encode(['quote_number' => $quoteNumber], JSON_UNESCAPED_UNICODE),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'created_at' => date('Y-m-d H:i:s'),
                ]);
                return $quoteId;
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Teklif oluşturulamadı. Lütfen tekrar deneyin.'];
        }

        return ['ok' => true, 'message' => 'Teklif talebiniz alındı. Satış ekibimiz en kısa sürede fiyatlandıracaktır.', 'quote_number' => $quoteNumber, 'quote_id' => $quoteId];
    }

    public static function listForCompany(int $companyId): array
    {
        return Database::fetchAll(
            'SELECT q.*, (SELECT COUNT(*) FROM quote_items qi WHERE qi.quote_id = q.id) AS item_count
             FROM quotes q WHERE q.company_id = :co ORDER BY q.created_at DESC, q.id DESC',
            [':co' => $companyId]
        );
    }

    public static function find(int $companyId, string $quoteNumber): ?array
    {
        return Database::fetch(
            'SELECT * FROM quotes WHERE quote_number = :n AND company_id = :co LIMIT 1',
            [':n' => $quoteNumber, ':co' => $companyId]
        );
    }

    public static function items(int $quoteId): array
    {
        return Database::fetchAll(
            'SELECT qi.*, p.sku, p.name, p.slug, p.main_image, p.unit_type
             FROM quote_items qi JOIN products p ON p.id = qi.product_id
             WHERE qi.quote_id = :id ORDER BY qi.id ASC',
            [':id' => $quoteId]
        );
    }

    public static function convertToOrder(int $companyId, int $userId, string $quoteNumber): array
    {
        $quote = self::find($companyId, $quoteNumber);
        if ($quote === null) {
            return ['ok' => false, 'message' => 'Teklif bulunamadı.'];
        }
        if ($quote['status'] !== 'offered') {
            return ['ok' => false, 'message' => 'Yalnızca fiyatlandırılmış (offered) teklifler siparişe dönüştürülebilir.'];
        }
        $items = self::items((int) $quote['id']);
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Teklif kalemi bulunamadı.'];
        }
        $address = Database::fetch(
            'SELECT id FROM company_addresses WHERE company_id = :co ORDER BY is_default DESC, id ASC LIMIT 1',
            [':co' => $companyId]
        );
        if ($address === null) {
            return ['ok' => false, 'message' => 'Sipariş için kayıtlı adres bulunamadı.'];
        }

        $cartId = CartService::activeCart($companyId, $userId);
        Database::delete('cart_items', 'cart_id = :c', [':c' => $cartId]);

        foreach ($items as $item) {
            if ($item['offered_price'] === null) {
                return ['ok' => false, 'message' => 'Teklifte fiyatlandırılmamış kalem var.'];
            }
            $warehouseId = CartService::pickWarehouse($companyId, (int) $item['product_id'], (int) $item['quantity']);
            $offered = round((float) $item['offered_price'], 2);
            $current = round((float) $item['current_net_price'], 2);
            $discount = $current > 0 ? round((1 - $offered / $current) * 100, 2) : 0.0;
            $product = Database::fetch('SELECT vat_rate FROM products WHERE id = :id', [':id' => $item['product_id']]);
            Database::insert('cart_items', [
                'cart_id' => $cartId,
                'product_id' => (int) $item['product_id'],
                'warehouse_id' => $warehouseId,
                'quantity' => (int) $item['quantity'],
                'unit_list_price' => $current,
                'applied_discount_rate' => $discount,
                'unit_net_price' => $offered,
                'vat_rate' => (float) ($product['vat_rate'] ?? 20.0),
                'line_total_net' => round($offered * (int) $item['quantity'], 2),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $cartItems = CartService::items($cartId);
        $result = OrderService::createFromCart($companyId, $userId, [
            'items' => $cartItems,
            'shipping_address_id' => (int) $address['id'],
            'billing_address_id' => (int) $address['id'],
            'payment_method' => 'open_account',
            'customer_po_number' => $quote['quote_number'],
            'customer_note' => 'Teklif dönüşümü: ' . $quote['quote_number'],
        ]);
        if (!$result['ok']) {
            return $result;
        }

        Database::update('quotes', [
            'status' => 'accepted',
            'converted_order_id' => $result['order_id'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $quote['id']]);

        return ['ok' => true, 'message' => 'Teklif siparişe dönüştürüldü. Sipariş No: ' . $result['order_number'], 'order_number' => $result['order_number']];
    }
}
