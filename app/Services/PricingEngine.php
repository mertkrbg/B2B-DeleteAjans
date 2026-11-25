<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Dinamik B2B Fiyatlandırma Motoru (5 adımlı şelale):
 * 1) Döviz çevrimi  2) Özel fiyat listesi  3) Bayi cari iskontosu
 * 4) Kademeli miktar iskontosu (bileşik)  5) KDV
 * Tüm parasal hesaplamalar bcmath ile yapılır.
 */
class PricingEngine
{
    private int $companyId;
    private float $companyDiscount;
    private ?int $priceListId;
    private array $rates = [];
    /** @var array<int, array<int, array{min_qty:int,max_qty:?int,discount_rate:float}>> */
    private array $tierCache = [];
    /** @var array<int, float> */
    private array $specialCache = [];
    private array $loadedTierProducts = [];
    private array $specialsLoadedIds = [];

    private function __construct(int $companyId, float $companyDiscount, ?int $priceListId)
    {
        $this->companyId = $companyId;
        $this->companyDiscount = $companyDiscount;
        $this->priceListId = $priceListId;
    }

    public static function forCompany(int $companyId): self
    {
        $company = Database::fetch(
            'SELECT base_discount_rate, price_list_id FROM companies WHERE id = :id LIMIT 1',
            [':id' => $companyId]
        );
        $engine = new self(
            $companyId,
            (float) ($company['base_discount_rate'] ?? 0.0),
            isset($company['price_list_id']) && $company['price_list_id'] !== null ? (int) $company['price_list_id'] : null
        );
        foreach (Database::fetchAll('SELECT currency_code, selling_rate FROM currency_rates') as $rate) {
            $engine->rates[$rate['currency_code']] = (float) $rate['selling_rate'];
        }
        return $engine;
    }

    public function companyDiscount(): float
    {
        return $this->companyDiscount;
    }

    public function loadFor(array $productIds): void
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (empty($productIds)) {
            return;
        }
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $tierProducts = array_diff($productIds, $this->loadedTierProducts);
        if (!empty($tierProducts)) {
            $ph = implode(',', array_fill(0, count($tierProducts), '?'));
            $rows = Database::fetchAll(
                "SELECT product_id, min_qty, max_qty, discount_rate FROM product_tier_prices
                 WHERE product_id IN ($ph) ORDER BY min_qty ASC",
                array_values($tierProducts)
            );
            foreach ($rows as $row) {
                $pid = (int) $row['product_id'];
                $this->tierCache[$pid][] = [
                    'min_qty' => (int) $row['min_qty'],
                    'max_qty' => $row['max_qty'] !== null ? (int) $row['max_qty'] : null,
                    'discount_rate' => (float) $row['discount_rate'],
                ];
            }
            foreach ($tierProducts as $pid) {
                $this->tierCache[$pid] = $this->tierCache[$pid] ?? [];
                $this->loadedTierProducts[] = (int) $pid;
            }
        }

        if ($this->priceListId !== null) {
            $missing = array_values(array_diff($productIds, $this->specialsLoadedIds));
            if (!empty($missing)) {
                $mph = implode(',', array_fill(0, count($missing), '?'));
                $rows = Database::fetchAll(
                    "SELECT product_id, special_price FROM price_list_items
                     WHERE price_list_id = ? AND product_id IN ($mph)",
                    array_merge([$this->priceListId], $missing)
                );
                foreach ($rows as $row) {
                    $this->specialCache[(int) $row['product_id']] = (float) $row['special_price'];
                }
                $this->specialsLoadedIds = array_merge($this->specialsLoadedIds, $missing);
            }
        }
    }

    /**
     * @param array $product products tablosundan bir satır (id, base_list_price, currency, vat_rate)
     */
    public function price(array $product, int $qty = 1): array
    {
        $productId = (int) $product['id'];
        $this->loadFor([$productId]);

        $currency = (string) ($product['currency'] ?? 'TRY');
        $rate = $currency !== 'TRY' ? ($this->rates[$currency] ?? 1.0) : 1.0;
        $rawBase = isset($this->specialCache[$productId])
            ? $this->specialCache[$productId]
            : (float) $product['base_list_price'];

        $convertedBase = bcmul((string) $rawBase, (string) $rate, 6);

        $d1 = bcdiv((string) $this->companyDiscount, '100', 6);
        $afterCompany = bcmul($convertedBase, bcsub('1', $d1, 6), 6);

        $tierRate = 0.0;
        foreach ($this->tierCache[$productId] ?? [] as $tier) {
            $min = $tier['min_qty'];
            $max = $tier['max_qty'];
            if ($qty >= $min && ($max === null || $qty <= $max)) {
                $tierRate = $tier['discount_rate'];
                break;
            }
        }
        $d2 = bcdiv((string) $tierRate, '100', 6);
        $net = bcmul($afterCompany, bcsub('1', $d2, 6), 6);

        $unitNet = round((float) $net, 4);
        $vat = (float) ($product['vat_rate'] ?? 20.0);
        $unitGross = round($unitNet * (1 + $vat / 100), 4);

        return [
            'base_list_price'  => (float) $product['base_list_price'],
            'currency'         => $currency,
            'rate'             => $rate,
            'effective_base'   => (float) $convertedBase,
            'special_price'    => isset($this->specialCache[$productId]) ? $this->specialCache[$productId] : null,
            'company_discount' => $this->companyDiscount,
            'tier_discount'    => $tierRate,
            'unit_net'         => $unitNet,
            'vat_rate'         => $vat,
            'unit_gross'       => $unitGross,
            'tiers'            => $this->tierCache[$productId] ?? [],
        ];
    }

    public function priceMany(array $products, ?int $qty = null): array
    {
        $this->loadFor(array_map(static fn ($p) => (int) $p['id'], $products));
        $out = [];
        foreach ($products as $product) {
            $effectiveQty = $qty ?? (int) ($product['min_order_qty'] ?? 1);
            if ($effectiveQty < 1) {
                $effectiveQty = 1;
            }
            $out[(int) $product['id']] = $this->price($product, $effectiveQty);
        }
        return $out;
    }
}
