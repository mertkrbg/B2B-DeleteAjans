<?php $products = $products ?? []; $curSymbol = static fn (string $c): string => $c === 'EUR' ? '€' : ($c === 'USD' ? '$' : '₺'); ?>
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Ürünler & Stok</h1>
        <p class="mt-1 text-sm text-slate-500">Ürün kartları, çoklu depo stokları ve kademeli iskontoları yönetin.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <form action="<?= url('admin/urunler') ?>" method="GET" class="flex gap-2">
            <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="SKU / ürün adı" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm sm:w-52">
            <button type="submit" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Ara</button>
        </form>
        <a href="<?= url('admin/urunler/yeni') ?>" class="inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="plus" class="h-4 w-4"></i> Yeni Ürün</a>
    </div>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2.5 font-semibold">Ürün</th><th class="px-3 py-2.5 font-semibold">Kategori</th><th class="px-3 py-2.5 font-semibold">Marka</th><th class="px-3 py-2.5 text-right font-semibold">Stok</th><th class="px-3 py-2.5 text-right font-semibold">Liste Fiyatı</th><th class="px-3 py-2.5 font-semibold">Durum</th><th class="px-4 py-2.5"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($products as $p): ?>
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3"><div class="flex items-center gap-2.5"><span class="h-9 w-12 shrink-0 overflow-hidden rounded-lg bg-slate-100"><img src="<?= asset((string) $p['main_image']) ?>" alt="" class="h-full w-full object-cover"></span><div><div class="font-semibold text-slate-800"><?= e((string) $p['name']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $p['sku']) ?></div></div></div></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) ($p['category_name'] ?? '-')) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) ($p['brand_name'] ?? '-')) ?></td>
                        <td class="px-3 py-3 text-right"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= (int) $p['stock'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $p['stock'] ?></span></td>
                        <td class="px-3 py-3 text-right font-mono text-xs text-slate-800"><?= e(money($p['base_list_price'], $curSymbol((string) $p['currency']))) ?></td>
                        <td class="px-3 py-3"><span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?= (int) $p['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' ?>"><?= (int) $p['is_active'] ? 'Aktif' : 'Pasif' ?></span></td>
                        <td class="px-4 py-3 text-right"><a href="<?= url('admin/urunler/' . $p['id']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Düzenle <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?><tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Ürün bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
