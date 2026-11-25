<?php
$counts = $counts ?? [];
$tabs = ['' => 'Tümü', 'pending_company_approval' => 'Şirket Onayı', 'pending_admin_approval' => 'Finans Onayı', 'approved' => 'Onaylandı', 'shipped' => 'Sevk Edildi', 'delivered' => 'Teslim'];
?>
<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Sipariş Yönetimi</h1>
    <p class="mt-1 text-sm text-slate-500">Gelen siparişleri onaylayın, sevk edin ve fatura kesin.</p>
</div>

<div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
    <?php foreach ($tabs as $key => $label): ?>
        <?php $count = $key === '' ? ($counts['all'] ?? 0) : ($counts[$key] ?? 0); ?>
        <a href="<?= url('admin/siparisler' . ($key !== '' ? '?durum=' . $key : '')) ?>" class="shrink-0 rounded-xl border px-3 py-1.5 text-xs font-semibold <?= $statusFilter === $key ? 'border-action bg-action text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>"><?= e($label) ?> <span class="opacity-70">(<?= (int) $count ?>)</span></a>
    <?php endforeach; ?>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2.5 font-semibold">Sipariş</th><th class="px-3 py-2.5 font-semibold">Bayi</th><th class="px-3 py-2.5 font-semibold">Tarih</th><th class="px-3 py-2.5 font-semibold">Kalem</th><th class="px-3 py-2.5 font-semibold">Durum</th><th class="px-3 py-2.5 text-right font-semibold">Tutar</th><th class="px-4 py-2.5"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($orders as $o): ?>
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800"><?= e((string) $o['order_number']) ?><?php if (!empty($o['invoice_number'])): ?><span class="block text-[10px] font-normal text-slate-400"><?= e((string) $o['invoice_number']) ?></span><?php endif; ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) $o['company_title']) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e(tr_date((string) $o['created_at'])) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= (int) $o['item_count'] ?> kalem</td>
                        <td class="px-3 py-3"><span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e(order_status_classes((string) $o['status'])) ?>"><?= e(order_status_label((string) $o['status'])) ?></span></td>
                        <td class="px-3 py-3 text-right font-mono text-xs font-semibold text-slate-900"><?= e(money($o['grand_total'])) ?></td>
                        <td class="px-4 py-3 text-right"><a href="<?= url('admin/siparisler/' . $o['order_number']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detay <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?><tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Sipariş bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
