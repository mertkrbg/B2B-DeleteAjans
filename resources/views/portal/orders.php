<?php
$statusFilter = $statusFilter ?? '';
$counts = $counts ?? [];
$tabs = [
    '' => 'Tümü',
    'pending_company_approval' => 'Şirket Onayı',
    'pending_admin_approval' => 'Finans Onayı',
    'approved' => 'Onaylandı',
    'preparing' => 'Hazırlanıyor',
    'shipped' => 'Sevk Edildi',
    'delivered' => 'Teslim Edildi',
];
$totalAll = array_sum($counts);
?>
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Siparişlerim</h1>
        <p class="mt-1 text-sm text-slate-500">Geçmiş siparişlerinizi ve sevkiyat durumlarını takip edin.</p>
    </div>
    <a href="<?= url('portal/hizli-siparis') ?>" class="inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="plus" class="h-4 w-4"></i> Yeni Sipariş</a>
</div>

<div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
    <?php foreach ($tabs as $key => $label): ?>
        <?php $count = $key === '' ? $totalAll : ($counts[$key] ?? 0); ?>
        <a href="<?= url('portal/siparisler' . ($key !== '' ? '?durum=' . urlencode($key) : '')) ?>" class="shrink-0 rounded-xl border px-3 py-1.5 text-xs font-semibold <?= $statusFilter === $key ? 'border-action bg-action text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>"><?= e($label) ?> <span class="opacity-70">(<?= (int) $count ?>)</span></a>
    <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <i data-lucide="package" class="mx-auto h-12 w-12 text-slate-300"></i>
        <h2 class="mt-3 text-base font-semibold text-slate-800">Sipariş bulunamadı</h2>
        <p class="mt-1 text-sm text-slate-500">Seçilen durumda siparişiniz yok.</p>
        <a href="<?= url('portal/katalog') ?>" class="mt-4 inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="layout-grid" class="h-4 w-4"></i> Kataloğa Git</a>
    </div>
<?php else: ?>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold">Sipariş No</th>
                        <th class="px-3 py-2.5 font-semibold">Tarih</th>
                        <th class="px-3 py-2.5 font-semibold">Kalem</th>
                        <th class="px-3 py-2.5 font-semibold">Ödeme</th>
                        <th class="px-3 py-2.5 font-semibold">Durum</th>
                        <th class="px-3 py-2.5 text-right font-semibold">Tutar</th>
                        <th class="px-4 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($orders as $order): ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800"><?= e((string) $order['order_number']) ?><?php if (!empty($order['customer_po_number'])): ?><span class="block text-[10px] font-normal text-slate-400">PO: <?= e((string) $order['customer_po_number']) ?></span><?php endif; ?></td>
                            <td class="px-3 py-3 text-slate-600"><?= e(tr_date((string) $order['created_at'])) ?></td>
                            <td class="px-3 py-3 text-slate-600"><?= (int) $order['item_count'] ?> kalem</td>
                            <td class="px-3 py-3 text-xs text-slate-600"><?= e(payment_method_label((string) $order['payment_method'])) ?></td>
                            <td class="px-3 py-3"><span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold <?= e(order_status_classes((string) $order['status'])) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                            <td class="px-3 py-3 text-right font-mono font-semibold text-slate-900"><?= e(money($order['grand_total'])) ?></td>
                            <td class="px-4 py-3 text-right"><a href="<?= url('portal/siparis/' . $order['order_number']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Detay <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
