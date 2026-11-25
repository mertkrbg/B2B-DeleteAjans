<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Class CartService
 *
 * B2B Sepet ve Sipariş Şablonları Servisi.
 * Aktif sepet yönetimi, barkod/SKU ile ürün ekleme, şablon kaydetme
 * ve dinamik fiyat motoru entegrasyonunu yürütür.
 *
 * @package App\Services
 */
class CartService
{
    public static function activeCart(int $companyId, int $userId): int
    {
        $cart = Database::fetch(
            'SELECT id FROM carts WHERE user_id = :uid AND is_template = 0 ORDER BY id DESC LIMIT 1',
            [':uid' => $userId]
        );
        if ($cart !== null) {
            return (int) $cart['id'];
        }
        return Database::insert('carts', [
            'company_id' => $companyId,
            'user_id'    => $userId,
            'cart_name'  => 'Aktif Sepet',
            'is_template'=> 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function product(string $skuOrBarcodeOrId): ?array
    {
        if (ctype_digit($skuOrBarcodeOrId)) {
            $product = Database::fetch('SELECT * FROM products WHERE id = :v AND is_active = 1', [':v' => (int) $skuOrBarcodeOrId]);
            if ($product !== null) {
                return $product;
            }
        }
        return Database::fetch(
            'SELECT * FROM products WHERE is_active = 1 AND (sku = :v1 OR barcode = :v2 OR oem_code = :v3) LIMIT 1',
            [':v1' => $skuOrBarcodeOrId, ':v2' => $skuOrBarcodeOrId, ':v3' => $skuOrBarcodeOrId]
        );
    }

    public static function availableStock(int $productId, ?int $warehouseId = null): int
    {
        if ($warehouseId !== null) {
            return (int) Database::fetchColumn(
                'SELECT COALESCE(SUM(physical_qty - reserved_qty), 0) FROM product_stocks WHERE product_id = :p AND warehouse_id = :w',
                [':p' => $productId, ':w' => $warehouseId]
            );
        }
        return (int) Database::fetchColumn(
            'SELECT COALESCE(SUM(physical_qty - reserved_qty), 0) FROM product_stocks WHERE product_id = :p',
            [':p' => $productId]
        );
    }

    public static function pickWarehouse(int $companyId, int $productId, ?int $requiredQty = null): ?int
    {
        $needed = $requiredQty !== null ? max(1, $requiredQty) : 1;
        $company = Database::fetch('SELECT default_warehouse_id FROM companies WHERE id = :id', [':id' => $companyId]);
        $preferred = isset($company['default_warehouse_id']) && $company['default_warehouse_id'] !== null ? (int) $company['default_warehouse_id'] : null;
        if ($preferred !== null && self::availableStock($productId, $preferred) >= $needed) {
            return $preferred;
        }
        $row = Database::fetch(
            'SELECT ps.warehouse_id, (ps.physical_qty - ps.reserved_qty) AS avail
             FROM product_stocks ps JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE ps.product_id = :p AND w.is_active = 1 AND (ps.physical_qty - ps.reserved_qty) >= :need
             ORDER BY w.priority_order ASC LIMIT 1',
            [':p' => $productId, ':need' => $needed]
        );
        if ($row !== null) {
            return (int) $row['warehouse_id'];
        }
        if ($preferred !== null) {
            return $preferred;
        }
        $any = Database::fetch(
            'SELECT ps.warehouse_id FROM product_stocks ps JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE ps.product_id = :p AND w.is_active = 1 ORDER BY w.priority_order ASC LIMIT 1',
            [':p' => $productId]
        );
        return $any !== null ? (int) $any['warehouse_id'] : null;
    }

    /**
     * @return array{ok:bool,message:string,cart_id?:int}
     */
    public static function addItem(int $companyId, int $userId, int $productId, int $qty, ?int $warehouseId = null): array
    {
        $product = Database::fetch('SELECT * FROM products WHERE id = :id AND is_active = 1 LIMIT 1', [':id' => $productId]);
        if ($product === null) {
            return ['ok' => false, 'message' => 'Ürün bulunamadı veya pasif durumda.'];
        }
        $min = max(1, (int) $product['min_order_qty']);
        $step = max(1, (int) $product['order_step_qty']);
        if ($qty < $min) {
            return ['ok' => false, 'message' => $product['sku'] . ': Minimum sipariş adedi ' . $min . ' ' . $product['unit_type'] . '.'];
        }
        if ($step > 1 && (($qty - $min) % $step) !== 0 && ($qty % $step) !== 0) {
            return ['ok' => false, 'message' => $product['sku'] . ': Adet, ' . $step . ' ve katları şeklinde olmalıdır.'];
        }
        if ($warehouseId === null) {
            $warehouseId = self::pickWarehouse($companyId, $productId, $qty);
        }
        $available = self::availableStock($productId, $warehouseId);
        if ($available < $qty) {
            return ['ok' => false, 'message' => $product['sku'] . ': Yetersiz stok (kullanılabilir ' . $available . ').'];
        }

        $cartId = self::activeCart($companyId, $userId);

        $existing = Database::fetch(
            'SELECT * FROM cart_items WHERE cart_id = :c AND product_id = :p AND warehouse_id <=> :w LIMIT 1',
            [':c' => $cartId, ':p' => $productId, ':w' => $warehouseId]
        );

        $newQty = $existing !== null ? (int) $existing['quantity'] + $qty : $qty;
        if ($newQty > $available) {
            return ['ok' => false, 'message' => $product['sku'] . ': Sepetteki toplam adet stoktan fazla olamaz (kullanılabilir ' . $available . ').'];
        }

        $engine = PricingEngine::forCompany($companyId);
        $price = $engine->price($product, $newQty);
        $unitNet = round($price['unit_net'], 2);
        $discountRate = $price['effective_base'] > 0
            ? round((1 - $unitNet / $price['effective_base']) * 100, 2)
            : 0.0;

        $payload = [
            'quantity'             => $newQty,
            'unit_list_price'      => $price['effective_base'],
            'applied_discount_rate'=> $discountRate,
            'unit_net_price'       => $unitNet,
            'vat_rate'             => $price['vat_rate'],
            'line_total_net'       => round($unitNet * $newQty, 2),
        ];

        if ($existing !== null) {
            Database::update('cart_items', $payload, 'id = :id', [':id' => $existing['id']]);
        } else {
            Database::insert('cart_items', array_merge($payload, [
                'cart_id'      => $cartId,
                'product_id'   => $productId,
                'warehouse_id' => $warehouseId,
                'created_at'   => date('Y-m-d H:i:s'),
            ]));
        }
        Database::update('carts', ['updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $cartId]);

        return ['ok' => true, 'message' => $product['name'] . ' sepete eklendi.', 'cart_id' => $cartId];
    }

    public static function addMany(int $companyId, int $userId, array $items): array
    {
        $added = 0;
        $errors = [];
        foreach ($items as $item) {
            $result = self::addItem($companyId, $userId, (int) $item['product_id'], (int) $item['quantity'], $item['warehouse_id'] ?? null);
            if ($result['ok']) {
                $added++;
            } else {
                $errors[] = $result['message'];
            }
        }
        return ['added' => $added, 'errors' => $errors];
    }

    public static function items(int $cartId): array
    {
        $rows = Database::fetchAll(
            'SELECT ci.*, p.sku, p.name, p.slug, p.main_image, p.unit_type, p.min_order_qty, p.order_step_qty,
                    w.name AS warehouse_name
             FROM cart_items ci
             JOIN products p ON p.id = ci.product_id
             LEFT JOIN warehouses w ON w.id = ci.warehouse_id
             WHERE ci.cart_id = :cid
             ORDER BY ci.id ASC',
            [':cid' => $cartId]
        );
        // Tek yuvarlama politikası: satır tutarı, görünen (2 haneli) birim fiyat × adet.
        foreach ($rows as &$row) {
            $row['line_total_net'] = round(round((float) $row['unit_net_price'], 2) * (int) $row['quantity'], 2);
        }
        unset($row);
        return $rows;
    }

    public static function totals(array $items): array
    {
        $net = '0';
        $vat = '0';
        foreach ($items as $item) {
            // Satırda gösterilen 2 haneli tutarla toplam birebir tutarlı olsun diye
            // önce satırı 2 haneye yuvarlıyor, sonra bcmath ile topluyoruz.
            $lineNet = number_format((float) $item['line_total_net'], 2, '.', '');
            $lineVat = number_format((float) $lineNet * (float) $item['vat_rate'] / 100, 2, '.', '');
            $net = bcadd($net, $lineNet, 2);
            $vat = bcadd($vat, $lineVat, 2);
        }
        $gross = bcadd($net, $vat, 2);
        return [
            'net'   => (float) $net,
            'vat'   => (float) $vat,
            'gross' => (float) $gross,
            'count' => array_sum(array_map(static fn ($i) => (int) $i['quantity'], $items)),
        ];
    }

    public static function updateQuantity(int $cartId, int $itemId, int $qty, int $companyId, ?int $warehouseId = null): array
    {
        $item = Database::fetch(
            'SELECT ci.*, p.min_order_qty, p.order_step_qty, p.sku FROM cart_items ci JOIN products p ON p.id = ci.product_id
             WHERE ci.id = :id AND ci.cart_id = :c LIMIT 1',
            [':id' => $itemId, ':c' => $cartId]
        );
        if ($item === null) {
            return ['ok' => false, 'message' => 'Sepet kalemi bulunamadı.'];
        }
        $min = max(1, (int) $item['min_order_qty']);
        $step = max(1, (int) $item['order_step_qty']);
        if ($qty < $min) {
            return ['ok' => false, 'message' => $item['sku'] . ': Minimum adet ' . $min . '.'];
        }
        if ($step > 1 && (($qty - $min) % $step) !== 0 && ($qty % $step) !== 0) {
            return ['ok' => false, 'message' => $item['sku'] . ': Adet ' . $step . ' ve katları olmalıdır.'];
        }
        $targetWarehouse = $warehouseId !== null ? $warehouseId : ($item['warehouse_id'] !== null ? (int) $item['warehouse_id'] : null);
        if ($warehouseId !== null) {
            $wh = Database::fetch('SELECT id FROM warehouses WHERE id = :id AND is_active = 1', [':id' => $warehouseId]);
            if ($wh === null) {
                return ['ok' => false, 'message' => 'Geçersiz depo seçimi.'];
            }
        }
        $available = self::availableStock((int) $item['product_id'], $targetWarehouse);
        if ($qty > $available) {
            return ['ok' => false, 'message' => $item['sku'] . ': Yetersiz stok (kullanılabilir ' . $available . ').'];
        }
        $product = Database::fetch('SELECT * FROM products WHERE id = :id', [':id' => $item['product_id']]);
        $engine = PricingEngine::forCompany($companyId);
        $price = $engine->price($product, $qty);
        $unitNet = round($price['unit_net'], 2);
        $discountRate = $price['effective_base'] > 0 ? round((1 - $unitNet / $price['effective_base']) * 100, 2) : 0.0;

        Database::update('cart_items', [
            'quantity'             => $qty,
            'warehouse_id'         => $targetWarehouse,
            'unit_list_price'      => $price['effective_base'],
            'applied_discount_rate'=> $discountRate,
            'unit_net_price'       => $unitNet,
            'line_total_net'       => round($unitNet * $qty, 2),
        ], 'id = :id', [':id' => $itemId]);

        return ['ok' => true, 'message' => 'Sepet güncellendi.'];
    }

    public static function saveSettings(int $cartId, string $poNumber, string $notes): void
    {
        Database::update('carts', [
            'po_number'  => $poNumber !== '' ? $poNumber : null,
            'notes'      => $notes !== '' ? $notes : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $cartId]);
    }

    public static function saveTemplate(int $companyId, int $userId, string $name): array
    {
        $cartId = self::activeCart($companyId, $userId);
        $items = self::items($cartId);
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Boş sepet şablon olarak kaydedilemez.'];
        }
        $templateId = Database::insert('carts', [
            'company_id' => $companyId, 'user_id' => $userId,
            'cart_name'  => $name !== '' ? mb_substr($name, 0, 150) : 'Sepet Şablonu',
            'is_template'=> 1, 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        foreach ($items as $item) {
            Database::insert('cart_items', [
                'cart_id' => $templateId, 'product_id' => $item['product_id'], 'warehouse_id' => $item['warehouse_id'],
                'quantity' => $item['quantity'], 'unit_list_price' => $item['unit_list_price'],
                'applied_discount_rate' => $item['applied_discount_rate'], 'unit_net_price' => $item['unit_net_price'],
                'vat_rate' => $item['vat_rate'], 'line_total_net' => $item['line_total_net'],
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
        return ['ok' => true, 'message' => 'Sepet "' . $name . '" adıyla şablon olarak kaydedildi.', 'template_id' => $templateId];
    }

    public static function templates(int $companyId, int $userId): array
    {
        return Database::fetchAll(
            'SELECT c.id, c.cart_name, c.updated_at, COUNT(ci.id) AS item_count,
                    COALESCE(SUM(ROUND(ROUND(ci.unit_net_price, 2) * ci.quantity, 2)), 0) AS total_net
             FROM carts c LEFT JOIN cart_items ci ON ci.cart_id = c.id
             WHERE c.company_id = :co AND c.user_id = :u AND c.is_template = 1
             GROUP BY c.id ORDER BY c.updated_at DESC',
            [':co' => $companyId, ':u' => $userId]
        );
    }

    public static function loadTemplate(int $templateId, int $companyId, int $userId): array
    {
        $template = Database::fetch(
            'SELECT * FROM carts WHERE id = :id AND company_id = :co AND user_id = :u AND is_template = 1 LIMIT 1',
            [':id' => $templateId, ':co' => $companyId, ':u' => $userId]
        );
        if ($template === null) {
            return ['ok' => false, 'message' => 'Şablon bulunamadı.'];
        }
        $items = self::items($templateId);
        if (empty($items)) {
            return ['ok' => false, 'message' => 'Şablon boş.'];
        }
        $added = 0;
        $errors = [];
        foreach ($items as $item) {
            $result = self::addItem($companyId, $userId, (int) $item['product_id'], (int) $item['quantity'], $item['warehouse_id'] !== null ? (int) $item['warehouse_id'] : null);
            if ($result['ok']) {
                $added++;
            } else {
                $errors[] = $result['message'];
            }
        }
        return ['ok' => $added > 0, 'added' => $added, 'errors' => $errors, 'message' => $added . ' kalem şablondan sepete yüklendi.'];
    }

    public static function removeItem(int $cartId, int $itemId): bool
    {
        return Database::delete('cart_items', 'id = :id AND cart_id = :c', [':id' => $itemId, ':c' => $cartId]) > 0;
    }
}
