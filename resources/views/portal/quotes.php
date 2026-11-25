<?php $quotes = $quotes ?? []; ?>
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Tekliflerim (RFQ)</h1>
        <p class="mt-1 text-sm text-slate-500">Proje bazlı ve toplu alımlar için özel fiyat taleplerinizi yönetin.</p>
    </div>
    <a href="<?= url('portal/teklif-olustur') ?>" class="inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="file-plus" class="h-4 w-4"></i> Yeni Teklif Talebi</a>
</div>

<?php if (empty($quotes)): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <i data-lucide="file-signature" class="mx-auto h-12 w-12 text-slate-300"></i>
        <h2 class="mt-3 text-base font-semibold text-slate-800">Henüz teklif talebiniz yok</h2>
        <p class="mt-1 text-sm text-slate-500">Sepetinizdeki ürünler için özel fiyat talebi oluşturabilirsiniz.</p>
        <a href="<?= url('portal/teklif-olustur') ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="plus" class="h-4 w-4"></i> Teklif Oluştur</a>
    </div>
<?php else: ?>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Teklif No</th>
                        <th class="px-3 py-2.5 font-semibold">Tarih</th>
                        <th class="px-3 py-2.5 font-semibold">Kalem</th>
                        <th class="px-3 py-2.5 font-semibold">Durum</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Teklif Tutarı</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($quotes as $q): ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800"><?= e((string) $q['quote_number']) ?></td>
                            <td class="px-3 py-3 text-slate-600"><?= e(tr_date((string) $q['created_at'])) ?></td>
                            <td class="px-3 py-3 text-slate-600"><?= (int) $q['item_count'] ?> kalem</td>
                            <td class="px-3 py-3"><span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold <?= e(quote_status_classes((string) $q['status'])) ?>"><?= e(quote_status_label((string) $q['status'])) ?></span></td>
                            <td class="px-3 py-3 text-right font-mono font-semibold text-slate-900"><?= $q['offered_total'] !== null ? e(money($q['offered_total'])) : '—' ?></td>
                            <td class="px-4 py-3 text-right"><a href="<?= url('portal/teklif/' . $q['quote_number']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detay <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
