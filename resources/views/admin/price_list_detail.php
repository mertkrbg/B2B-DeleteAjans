<?php
$products = $products ?? [];
$curSymbol = static fn (string $c): string => $c === 'EUR' ? '€' : ($c === 'USD' ? '$' : '₺');
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500"><a href="<?= url('admin/fiyat-listeleri') ?>" class="hover:text-action">Fiyat Listeleri</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><span class="font-mono font-medium text-slate-700"><?= e((string) $list['code']) ?></span></nav>

<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= e((string) $list['name']) ?></h1>
    <p class="mt-1 text-sm text-slate-500"><?= e((string) $list['code']) ?> · <?= e((string) $list['currency']) ?> · Modifiye: %<?= e(rtrim(rtrim(number_format((float) $list['discount_modifier'], 2, ',', '.'), '0'), ',')) ?> · Boş bırakılan ürünler liste fiyatını kullanır.</p>
</div>

<form action="<?= url('admin/fiyat-listeleri/' . $list['id']) ?>" method="POST">
    <?= csrf_field() ?>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr><th class="px-4 py-2.5 font-semibold">Ürün</th><th class="px-3 py-2.5 font-semibold">Marka</th><th class="px-3 py-2.5 text-right font-semibold">Liste Fiyatı</th><th class="px-3 py-2.5 text-right font-semibold">Özel Fiyat</th><th class="px-4 py-2.5 text-right font-semibold">Ek İskonto %</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td class="px-4 py-2.5"><div class="font-medium text-slate-800"><?= e((string) $p['name']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $p['sku']) ?></div></td>
                            <td class="px-3 py-2.5 text-xs text-slate-600"><?= e((string) ($p['brand_name'] ?? '-')) ?></td>
                            <td class="px-3 py-2.5 text-right font-mono text-xs text-slate-600"><?= e(money($p['base_list_price'], $curSymbol((string) $p['currency']))) ?></td>
                            <td class="px-3 py-2.5 text-right"><input type="number" step="0.0001" min="0" name="special_price[<?= (int) $p['id'] ?>]" value="<?= $p['special_price'] !== null ? e(number_format((float) $p['special_price'], 4, '.', '')) : '' ?>" placeholder="Liste fiyatı" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-xs tabular-nums"></td>
                            <td class="px-4 py-2.5 text-right"><input type="number" step="0.01" min="0" max="100" name="extra_discount[<?= (int) $p['id'] ?>]" value="<?= $p['extra_discount_rate'] !== null ? e(number_format((float) $p['extra_discount_rate'], 2, '.', '')) : '' ?>" placeholder="0" class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-xs tabular-nums"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-4 py-3">
            <a href="<?= url('admin/fiyat-listeleri') ?>" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">İptal</a>
            <button type="submit" class="rounded-xl bg-action px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Fiyatları Kaydet</button>
        </div>
    </div>
</form>
