<?php $lists = $lists ?? []; ?>
<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Fiyat Listeleri</h1>
    <p class="mt-1 text-sm text-slate-500">Bayilere atanabilen özel fiyat listeleri ve ürün bazlı özel fiyatları yönetin.</p>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="plus-circle" class="h-4 w-4 text-action"></i> Yeni Fiyat Listesi</h2>
    <form action="<?= url('admin/fiyat-listeleri') ?>" method="POST" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <?= csrf_field() ?>
        <div><label class="mb-1 block text-xs font-semibold text-slate-600">Kod</label><input type="text" name="code" placeholder="PL-XYZ" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
        <div class="lg:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">Ad</label><input type="text" name="name" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
        <div><label class="mb-1 block text-xs font-semibold text-slate-600">Para Birimi</label><select name="currency" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><option value="TRY">TRY</option><option value="USD">USD</option><option value="EUR">EUR</option></select></div>
        <div><label class="mb-1 block text-xs font-semibold text-slate-600">İskonto Mod.</label><input type="number" step="0.01" name="discount_modifier" value="0" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
        <div><label class="mb-1 block text-xs font-semibold text-slate-600">Geçerlilik Başlangıç</label><input type="date" name="valid_from" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
        <div class="flex items-end"><button type="submit" class="w-full rounded-xl bg-action px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Oluştur</button></div>
    </form>
</div>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2.5 font-semibold">Kod</th><th class="px-3 py-2.5 font-semibold">Ad</th><th class="px-3 py-2.5 font-semibold">Para Birimi</th><th class="px-3 py-2.5 text-right font-semibold">Mod.</th><th class="px-3 py-2.5 text-right font-semibold">Ürün</th><th class="px-3 py-2.5 text-right font-semibold">Bayi</th><th class="px-4 py-2.5"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($lists as $l): ?>
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800"><?= e((string) $l['code']) ?></td>
                        <td class="px-3 py-3 text-slate-700"><?= e((string) $l['name']) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) $l['currency']) ?></td>
                        <td class="px-3 py-3 text-right font-mono text-xs">%<?= e(rtrim(rtrim(number_format((float) $l['discount_modifier'], 2, ',', '.'), '0'), ',')) ?></td>
                        <td class="px-3 py-3 text-right text-xs"><?= (int) $l['item_count'] ?></td>
                        <td class="px-3 py-3 text-right text-xs"><?= (int) $l['company_count'] ?></td>
                        <td class="px-4 py-3 text-right"><a href="<?= url('admin/fiyat-listeleri/' . $l['id']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ürün Fiyatları <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
