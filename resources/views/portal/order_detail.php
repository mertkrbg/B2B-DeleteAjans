<?php
$status = (string) $order['status'];
$isPending = in_array($status, ['pending_company_approval', 'pending_admin_approval'], true);
$role = (string) ($authUser['role'] ?? '');
$canApprove = in_array($role, ['company_owner', 'purchasing_manager'], true);
$isCompanyPending = $status === 'pending_company_approval';
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500">
    <a href="<?= url('portal/siparisler') ?>" class="hover:text-action">Siparişlerim</a>
    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
    <span class="font-mono font-medium text-slate-700"><?= e((string) $order['order_number']) ?></span>
</nav>

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= e((string) $order['order_number']) ?></h1>
            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= e(order_status_classes($status)) ?>"><?= e(order_status_label($status)) ?></span>
        </div>
        <p class="mt-1 text-sm text-slate-500"><?= e(tr_date((string) $order['created_at'], true)) ?> · <?= e(payment_method_label((string) $order['payment_method'])) ?><?php if (!empty($order['customer_po_number'])): ?> · PO: <span class="font-mono"><?= e((string) $order['customer_po_number']) ?></span><?php endif; ?></p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <?php if ($isCompanyPending && $canApprove): ?>
            <form action="<?= url('portal/siparis/' . $order['order_number'] . '/onayla') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i data-lucide="check" class="h-4 w-4"></i> Onayla</button>
            </form>
            <form action="<?= url('portal/siparis/' . $order['order_number'] . '/reddet') ?>" method="POST" onsubmit="return confirm('Bu siparişi reddetmek istediğinize emin misiniz?');">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i> Reddet</button>
            </form>
        <?php endif; ?>
        <?php if (in_array($role, ['company_owner', 'purchasing_manager', 'buyer'], true)): ?>
            <form action="<?= url('portal/siparis/' . $order['order_number'] . '/tekrar') ?>" method="POST">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="rotate-ccw" class="h-4 w-4"></i> Tekrar Sipariş</button>
            </form>
        <?php endif; ?>
        <a href="<?= url('portal/siparis/' . $order['order_number'] . '/yazdir') ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="printer" class="h-4 w-4"></i> Yazdır</a>
        <a href="<?= url('portal/siparisler') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="arrow-left" class="h-4 w-4"></i> Listeye Dön</a>
    </div>
</div>

<?php if ($isPending): ?>
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        <i data-lucide="clock" class="mt-0.5 h-4 w-4 shrink-0"></i>
        <span><?= $status === 'pending_admin_approval' ? 'Sipariş, risk limiti nedeniyle finans ekibinin onayını bekliyor.' : 'Sipariş, firma içi onay bekliyor. Firma sahibi veya satın alma müdürü onayladığında işleme alınacaktır.' ?></span>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_340px]">
    <div class="space-y-4">
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Sipariş Kalemleri</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr><th class="px-4 py-2 font-semibold">Ürün</th><th class="px-3 py-2 text-right font-semibold">Adet</th><th class="px-3 py-2 text-right font-semibold">Birim Net</th><th class="px-3 py-2 text-right font-semibold">KDV</th><th class="px-4 py-2 text-right font-semibold">Tutar</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-4 py-3"><div class="font-medium text-slate-800"><?= e((string) $item['name_snapshot']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $item['sku_snapshot']) ?></div></td>
                                <td class="px-3 py-3 text-right tabular-nums text-slate-700"><?= (int) $item['quantity'] ?></td>
                                <td class="px-3 py-3 text-right font-mono text-xs text-slate-700"><?= e(money($item['unit_net_price'])) ?></td>
                                <td class="px-3 py-3 text-right text-xs text-slate-500">%<?= e(rtrim(rtrim(number_format((float) $item['vat_rate'], 2, ',', '.'), '0'), ',')) ?></td>
                                <td class="px-4 py-3 text-right font-mono font-semibold text-slate-900"><?= e(money($item['line_total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-4 py-3">
                <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Liste Toplamı</dt><dd class="font-mono"><?= e(money($order['subtotal_list'])) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">İskonto</dt><dd class="font-mono text-emerald-600">-<?= e(money($order['total_discount'])) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Ara Toplam</dt><dd class="font-mono"><?= e(money($order['subtotal_net'])) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">KDV</dt><dd class="font-mono"><?= e(money($order['total_vat'])) ?></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-1.5"><dt class="font-bold text-slate-900">Genel Toplam</dt><dd class="font-mono text-base font-bold text-slate-900"><?= e(money($order['grand_total'])) ?></dd></div>
                </dl>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500"><i data-lucide="truck" class="h-4 w-4"></i> Sevkiyat Adresi</h3>
                <p class="mt-2 text-sm font-semibold text-slate-800"><?= e((string) ($shipping['title'] ?? '-')) ?></p>
                <p class="text-xs leading-relaxed text-slate-500"><?= e((string) ($shipping['full_address'] ?? '')) ?></p>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wide text-slate-500"><i data-lucide="file-text" class="h-4 w-4"></i> Fatura Adresi</h3>
                <p class="mt-2 text-sm font-semibold text-slate-800"><?= e((string) ($billing['title'] ?? '-')) ?></p>
                <p class="text-xs leading-relaxed text-slate-500"><?= e((string) ($billing['full_address'] ?? '')) ?></p>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Durum Zaman Çizelgesi</h2>
        <ol class="mt-4 space-y-4">
            <?php foreach ($history as $h): ?>
                <li class="relative flex gap-3 pl-1">
                    <span class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full <?= $h['new_status'] === 'rejected' || $h['new_status'] === 'cancelled' ? 'bg-red-500' : 'bg-action' ?>"></span>
                    <div class="min-w-0">
                        <div class="text-xs font-semibold text-slate-800"><?= e(order_status_label((string) $h['new_status'])) ?></div>
                        <div class="text-[11px] text-slate-400"><?= e(tr_date((string) $h['created_at'], true)) ?><?= !empty($h['full_name']) ? ' · ' . e((string) $h['full_name']) : '' ?></div>
                        <?php if (!empty($h['comment'])): ?><div class="mt-0.5 text-[11px] text-slate-500"><?= e((string) $h['comment']) ?></div><?php endif; ?>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (empty($history)): ?><li class="text-xs text-slate-400">Kayıt yok.</li><?php endif; ?>
        </ol>
        <?php if (!empty($order['cargo_company']) || !empty($order['tracking_number'])): ?>
            <div class="mt-4 rounded-xl bg-slate-50 p-3 text-xs text-slate-600">
                <div class="font-semibold text-slate-700">Kargo Bilgisi</div>
                <?php if (!empty($order['cargo_company'])): ?><div><?= e((string) $order['cargo_company']) ?></div><?php endif; ?>
                <?php if (!empty($order['tracking_number'])): ?><div class="font-mono"><?= e((string) $order['tracking_number']) ?></div><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
