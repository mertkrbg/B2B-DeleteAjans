<?php
$curSymbol = static fn (string $c): string => $c === 'EUR' ? '€' : ($c === 'USD' ? '$' : '₺');
$totalAvailable = 0;
foreach ($stocks as $s) { $totalAvailable += (int) $s['available']; }
$inStock = $totalAvailable > 0;
$maxTier = 0.0;
foreach ($price['tiers'] as $t) { $maxTier = max($maxTier, (float) $t['discount_rate']); }
?>
<nav class="mb-4 flex flex-wrap items-center gap-1.5 text-xs text-slate-500">
    <a href="<?= url('portal') ?>" class="hover:text-action">Kokpit</a>
    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
    <a href="<?= url('portal/katalog?kategori=' . urlencode((string) $product['category_slug'])) ?>" class="hover:text-action"><?= e((string) ($product['category_name'] ?? 'Kategori')) ?></a>
    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
    <span class="font-medium text-slate-700"><?= e((string) $product['sku']) ?></span>
</nav>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="aspect-[4/3] w-full bg-slate-100">
            <img src="<?= asset((string) $product['main_image']) ?>" alt="<?= e((string) $product['name']) ?>" class="h-full w-full object-cover">
        </div>
        <div class="grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100 text-center">
            <div class="px-2 py-2.5"><div class="font-mono text-sm font-semibold text-slate-800"><?= e((string) $product['sku']) ?></div><div class="text-[10px] text-slate-400">Stok Kodu</div></div>
            <div class="px-2 py-2.5"><div class="font-mono text-sm font-semibold text-slate-800"><?= e((string) ($product['barcode'] ?? '-')) ?></div><div class="text-[10px] text-slate-400">Barkod</div></div>
            <div class="px-2 py-2.5"><div class="font-mono text-sm font-semibold text-slate-800"><?= e((string) ($product['oem_code'] ?? '-')) ?></div><div class="text-[10px] text-slate-400">OEM Kodu</div></div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-3">
            <div>
                <div class="text-xs font-semibold uppercase tracking-wide text-corporate"><?= e((string) ($product['brand_name'] ?? '')) ?></div>
                <h1 class="mt-1 text-xl font-bold leading-tight text-slate-900"><?= e((string) $product['name']) ?></h1>
            </div>
            <span class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold <?= $inStock ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= $inStock ? 'Stokta (' . $totalAvailable . ')' : 'Stok Yok' ?></span>
        </div>

        <?php if (!empty($product['short_description'])): ?>
            <p class="mt-2 text-sm leading-relaxed text-slate-600"><?= e((string) $product['short_description']) ?></p>
        <?php endif; ?>

        <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <div class="flex flex-wrap items-end gap-x-3 gap-y-1">
                <div>
                    <div class="text-[11px] text-slate-500">KDV Hariç Birim Fiyat</div>
                    <div class="font-mono text-2xl font-bold text-slate-900"><?= e(money($price['unit_net'])) ?></div>
                </div>
                <div class="pb-1 text-xs text-slate-500">KDV Dahil: <span class="font-mono font-semibold text-slate-700"><?= e(money($price['unit_gross'])) ?></span></div>
                <div class="pb-1 text-xs text-slate-400 line-through">Liste: <?= e(money($price['base_list_price'], $curSymbol($price['currency']))) ?></div>
            </div>
            <div class="mt-2 flex flex-wrap gap-1.5">
                <?php if ($price['company_discount'] > 0): ?>
                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">Bayi iskontosu %<?= e(rtrim(rtrim(number_format($price['company_discount'], 2, ',', '.'), '0'), ',')) ?></span>
                <?php endif; ?>
                <?php if ($price['special_price'] !== null): ?>
                    <span class="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">Özel fiyat listesi</span>
                <?php endif; ?>
                <?php if ($maxTier > 0): ?>
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">Toplu alımda %<?= e(rtrim(rtrim(number_format($maxTier, 2, ',', '.'), '0'), ',')) ?> seviyesine kadar iskonto</span>
                <?php endif; ?>
                <?php if ($price['currency'] !== 'TRY'): ?>
                    <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[11px] font-semibold text-slate-600"><?= e($price['currency']) ?> · Kur <?= e(number_format($price['rate'], 4, ',', '.')) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <form action="<?= url('portal/sepet/ekle') ?>" method="POST" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Sevkiyat Deposu</label>
                    <select name="warehouse_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <?php foreach ($stocks as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= (int) $s['available'] <= 0 ? 'disabled' : '' ?>><?= e((string) $s['name']) ?> — <?= (int) $s['available'] ?> <?= e((string) $product['unit_type']) ?><?= (int) $s['available'] <= 0 ? ' (stok yok)' : '' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-slate-600">Adet</label>
                    <input type="number" name="quantity" value="<?= (int) $product['min_order_qty'] ?>" min="<?= (int) $product['min_order_qty'] ?>" max="<?= $totalAvailable > 0 ? $totalAvailable : (int) $product['min_order_qty'] ?>" step="<?= (int) $product['order_step_qty'] ?>" aria-label="Adet" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums">
                </div>
            </div>
            <p class="text-[11px] text-slate-500">Minimum sipariş: <?= (int) $product['min_order_qty'] ?> <?= e((string) $product['unit_type']) ?> · Sipariş katı: <?= (int) $product['order_step_qty'] ?> · Koli içi: <?= (int) $product['box_quantity'] ?></p>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300" <?= $inStock ? '' : 'disabled' ?>>
                <i data-lucide="shopping-cart" class="h-4 w-4"></i> <?= $inStock ? 'Sepete Ekle' : 'Stokta Yok' ?>
            </button>
        </form>
    </div>
</div>

<div class="mt-5 grid grid-cols-1 gap-5 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Kademeli Fiyat Tablosu</h2>
        <p class="text-xs text-slate-500">Adet bazlı ek iskontolar</p>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-[10px] uppercase tracking-wide text-slate-400"><tr><th class="py-1.5 font-semibold">Adet Aralığı</th><th class="py-1.5 text-right font-semibold">Ek İskonto</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($price['tiers'] as $tier): ?>
                        <tr>
                            <td class="py-2 text-slate-600"><?= (int) $tier['min_qty'] ?> – <?= $tier['max_qty'] !== null ? (int) $tier['max_qty'] : '∞' ?></td>
                            <td class="py-2 text-right font-mono font-semibold text-amber">%<?= e(rtrim(rtrim(number_format((float) $tier['discount_rate'], 2, ',', '.'), '0'), ',')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($price['tiers'])): ?><tr><td colspan="2" class="py-2 text-slate-400">Kademeli iskonto tanımlı değil.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
        <h2 class="text-sm font-bold text-slate-800">Depo Bazlı Stok Durumu</h2>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[420px] text-left text-sm">
                <thead class="text-[10px] uppercase tracking-wide text-slate-400"><tr><th class="py-1.5 font-semibold">Depo</th><th class="py-1.5 text-right font-semibold">Kullanılabilir</th><th class="py-1.5 text-right font-semibold">Yolda</th><th class="py-1.5 text-right font-semibold">Termin</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($stocks as $s): ?>
                        <tr>
                            <td class="py-2"><span class="font-medium text-slate-700"><?= e((string) $s['name']) ?></span><span class="ml-1 text-xs text-slate-400">(<?= e((string) $s['city']) ?>)</span></td>
                            <td class="py-2 text-right"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= (int) $s['available'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $s['available'] ?></span></td>
                            <td class="py-2 text-right font-mono text-xs text-slate-500"><?= (int) $s['incoming_qty'] ?></td>
                            <td class="py-2 text-right text-xs text-slate-500"><?= $s['expected_date'] ? e(tr_date((string) $s['expected_date'])) : '-' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if (!empty($specs)): ?>
    <div class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Teknik Özellikler</h2>
        <dl class="mt-3 grid grid-cols-1 gap-x-8 gap-y-0 sm:grid-cols-2">
            <?php foreach ($specs as $key => $value): ?>
                <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm">
                    <dt class="text-slate-500"><?= e((string) $key) ?></dt>
                    <dd class="font-medium text-slate-800"><?= e((string) $value) ?></dd>
                </div>
            <?php endforeach; ?>
        </dl>
    </div>
<?php endif; ?>

<?php if (!empty($related)): ?>
    <div class="mt-6">
        <h2 class="mb-3 text-sm font-bold text-slate-800">Benzer Ürünler</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <?php foreach ($related as $rel): ?>
                <a href="<?= url('portal/urun/' . $rel['slug']) ?>" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm hover:shadow-md">
                    <div class="aspect-[4/3] bg-slate-100"><img src="<?= asset((string) $rel['main_image']) ?>" alt="<?= e((string) $rel['name']) ?>" loading="lazy" class="h-full w-full object-cover"></div>
                    <div class="p-3">
                        <div class="font-mono text-[11px] text-slate-500"><?= e((string) $rel['sku']) ?></div>
                        <div class="mt-0.5 line-clamp-2 min-h-[2.4rem] text-sm font-semibold text-slate-800"><?= e((string) $rel['name']) ?></div>
                        <div class="mt-1.5 font-mono text-sm font-bold text-slate-900"><?= e(money($rel['price']['unit_net'])) ?></div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
