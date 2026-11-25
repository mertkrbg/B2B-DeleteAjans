<?php
$quotes = $quotes ?? [];
$tabs = ['' => 'Tümü', 'requested' => 'Talep Alındı', 'reviewing' => 'İnceleniyor', 'offered' => 'Teklif Verildi', 'accepted' => 'Kabul', 'rejected' => 'Red'];
?>
<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Teklif Talepleri (RFQ)</h1>
    <p class="mt-1 text-sm text-slate-500">Bayilerden gelen özel fiyat taleplerini fiyatlandırın ve gönderin.</p>
</div>

<div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="<?= url('admin/teklifler' . ($key !== '' ? '?durum=' . $key : '')) ?>" class="shrink-0 rounded-xl border px-3 py-1.5 text-xs font-semibold <?= $statusFilter === $key ? 'border-action bg-action text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2.5 font-semibold">Teklif No</th><th class="px-3 py-2.5 font-semibold">Bayi</th><th class="px-3 py-2.5 font-semibold">Tarih</th><th class="px-3 py-2.5 font-semibold">Kalem</th><th class="px-3 py-2.5 font-semibold">Durum</th><th class="px-3 py-2.5 text-right font-semibold">Teklif Tutarı</th><th class="px-4 py-2.5"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($quotes as $q): ?>
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800"><?= e((string) $q['quote_number']) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) $q['company_title']) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e(tr_date((string) $q['created_at'])) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= (int) $q['item_count'] ?> kalem</td>
                        <td class="px-3 py-3"><span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e(quote_status_classes((string) $q['status'])) ?>"><?= e(quote_status_label((string) $q['status'])) ?></span></td>
                        <td class="px-3 py-3 text-right font-mono text-xs font-semibold text-slate-900"><?= $q['offered_total'] !== null ? e(money($q['offered_total'])) : '—' ?></td>
                        <td class="px-4 py-3 text-right"><a href="<?= url('admin/teklifler/' . $q['quote_number']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">İncele <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($quotes)): ?><tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Teklif talebi bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
