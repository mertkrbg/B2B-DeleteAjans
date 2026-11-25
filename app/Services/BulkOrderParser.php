<?php declare(strict_types=1);

namespace App\Services;

/**
 * Class BulkOrderParser
 *
 * Hızlı ve toplu sipariş giriş motoru.
 * Serbest metin, kopyala-yapıştır ve CSV/Excel girdi formatlarını ayrıştırarak
 * SKU ve adet eşleşmelerini normalize eder.
 *
 * @package App\Services
 */
class BulkOrderParser
{
    /**
     * Serbest metinden (SKU [TAB|;|,|boşluk] ADET) satırları ayrıştırır.
     * @return array<int, array{sku:string, qty:int, line:int}>
     */
    public static function parseText(string $text): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $rows = [];
        $lineNo = 0;
        foreach (explode("\n", $text) as $rawLine) {
            $lineNo++;
            $line = trim($rawLine);
            if ($line === '') {
                continue;
            }
            $parts = preg_split('/[\t;]+/', $line);
            if ($parts === false || count($parts) < 2) {
                $parts = preg_split('/\s*,\s*|\s{2,}|\s+/', $line);
            }
            $parts = array_values(array_filter(array_map('trim', $parts ?? []), static fn ($p) => $p !== ''));
            if (count($parts) < 2) {
                $rows[] = ['sku' => $parts[0] ?? $line, 'qty' => 0, 'line' => $lineNo];
                continue;
            }
            $sku = strtoupper($parts[0]);
            if (in_array($sku, ['SKU', 'STOKKODU', 'STOK_KODU', 'BARKOD'], true)) {
                continue;
            }
            $qtyRaw = preg_replace('/[^0-9]/', '', $parts[1]) ?? '0';
            $rows[] = ['sku' => $sku, 'qty' => (int) $qtyRaw, 'line' => $lineNo];
        }
        return $rows;
    }

    public static function parseFile(string $path): array
    {
        $content = @file_get_contents($path);
        if ($content === false) {
            return [];
        }
        return self::parseText($content);
    }

    /**
     * Satırları ürün/stok/fiyat kurallarıyla doğrular.
     * @return array{valid: array<int, array>, errors: array<int, string>}
     */
    public static function resolve(array $rows, int $companyId): array
    {
        $errors = [];
        $valid = [];
        $aggregated = [];
        foreach ($rows as $row) {
            $sku = (string) $row['sku'];
            if ($sku === '') {
                $errors[] = 'Satır ' . $row['line'] . ': Stok kodu boş.';
                continue;
            }
            if ((int) $row['qty'] <= 0) {
                $errors[] = 'Satır ' . $row['line'] . ' (' . $sku . '): Geçersiz adet.';
                continue;
            }
            $aggregated[$sku] = ($aggregated[$sku] ?? 0) + (int) $row['qty'];
        }

        $engine = PricingEngine::forCompany($companyId);
        foreach ($aggregated as $sku => $qty) {
            $product = CartService::product($sku);
            if ($product === null) {
                $errors[] = $sku . ': Ürün bulunamadı.';
                continue;
            }
            $min = max(1, (int) $product['min_order_qty']);
            $step = max(1, (int) $product['order_step_qty']);
            if ($qty < $min) {
                $errors[] = $sku . ': Minimum sipariş adedi ' . $min . '.';
                continue;
            }
            if ($step > 1 && (($qty - $min) % $step) !== 0 && ($qty % $step) !== 0) {
                $errors[] = $sku . ': Adet ' . $step . ' ve katları olmalıdır.';
                continue;
            }
            $warehouseId = CartService::pickWarehouse($companyId, (int) $product['id'], $qty);
            $available = CartService::availableStock((int) $product['id'], $warehouseId);
            if ($qty > $available) {
                $errors[] = $sku . ': Yetersiz stok (kullanılabilir ' . $available . ').';
                continue;
            }
            $price = $engine->price($product, $qty);
            $valid[] = [
                'product_id' => (int) $product['id'],
                'sku'        => (string) $product['sku'],
                'name'       => (string) $product['name'],
                'quantity'   => $qty,
                'unit_net'   => $price['unit_net'],
                'line_total' => round($price['unit_net'] * $qty, 4),
                'vat_rate'   => $price['vat_rate'],
            ];
        }
        return ['valid' => $valid, 'errors' => $errors];
    }

    public static function templateCsv(): string
    {
        return "SKU;ADET\nB2B-ELK-1001;10\nB2B-LGT-2001;4\nB2B-HRD-4001;5\n";
    }
}
