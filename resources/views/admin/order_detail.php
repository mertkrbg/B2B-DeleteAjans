<?php
$status = (string) $order['status'];
$isPending = in_array($status, ['pending_company_approval', 'pending_admin_approval'], true);
$hasInvoice = !empty($order['invoice_number']);
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500"><a href="<?= url('admin/siparisler') ?>" class="hover:text-action">Siparişler</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><span class="font-mono font-medium text-slate-700"><?= e((string) $order['order_number']) ?></span></nav>

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= e((string) $order['order_number']) ?></h1>
            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= e(order_status_classes($status)) ?>"><?= e(order_status_label($status)) ?></span>
            <?php if ($hasInvoice): ?><span class="inline-flex rounded-full bg-blue-50 px-2.5 py-0.5 text-[11px] font-semibold text-blue-700"><?= e((string) $order['invoice_number']) ?></span><?php endif; ?>
        </div>
        <p class="mt-1 text-sm text-slate-500"><?= e((string) ($company['title'] ?? '')) ?> · <?= e(tr_date((string) $order['created_at'], true)) ?> · <?= e(payment_method_label((string) $order['payment_method'])) ?></p>
    </div>
    <a href="<?= url('admin/siparisler') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="arrow-left" class="h-4 w-4"></i> Liste</a>
</div>

<div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sipariş Onayı</div>
        <?php if ($isPending): ?>
            <form action="<?= url('admin/siparisler/' . $order['order_number'] . '/onayla') ?>" method="POST" class="mt-2"><?= csrf_field() ?><button type="submit" class="w-full rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Siparişi Onayla</button></form>
        <?php else: ?>
            <p class="mt-2 text-xs text-slate-500">Onay bekleyen işlem yok.</p>
        <?php endif; ?>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sevkiyat</div>
        <form action="<?= url('admin/siparisler/' . $order['order_number'] . '/sevk') ?>" method="POST" class="mt-2 space-y-2">
            <?= csrf_field() ?>
            <input type="text" name="cargo_company" value="<?= e((string) ($order['cargo_company'] ?? '')) ?>" placeholder="Kargo firması" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs">
            <input type="text" name="tracking_number" value="<?= e((string) ($order['tracking_number'] ?? '')) ?>" placeholder="Takip numarası" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs">
            <button type="submit" class="w-full rounded-lg bg-navy px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Sevk Et</button>
        </form>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Fatura</div>
        <?php if ($hasInvoice): ?>
            <p class="mt-2 font-mono text-sm font-semibold text-slate-800"><?= e((string) $order['invoice_number']) ?></p>
            <p class="text-[11px] text-slate-500">Fatura kesildi ve cari hesaba borç işlendi.</p>
        <?php else: ?>
            <form action="<?= url('admin/siparisler/' . $order['order_number'] . '/fatura') ?>" method="POST" class="mt-2" onsubmit="return confirm('Fatura kesilip cari hesaba borç işlenecek. Onaylıyor musunuz?');">
                <?= csrf_field() ?><button type="submit" class="w-full rounded-lg bg-action px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Fatura Kes</button>
            </form>
            <p class="mt-1 text-[11px] text-slate-400">Tutar cari bakiyeye borç olarak yansır.</p>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_340px]">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Sipariş Kalemleri</h2></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[620px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2 font-semibold">Ürün</th><th class="px-3 py-2 text-right font-semibold">Adet</th><th class="px-3 py-2 text-right font-semibold">Birim Net</th><th class="px-4 py-2 text-right font-semibold">Tutar</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($items as $item): ?>
                        <tr><td class="px-4 py-2.5"><div class="font-medium text-slate-800"><?= e((string) $item['name_snapshot']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $item['sku_snapshot']) ?></div></td><td class="px-3 py-2.5 text-right tabular-nums"><?= (int) $item['quantity'] ?></td><td class="px-3 py-2.5 text-right font-mono text-xs"><?= e(money($item['unit_net_price'])) ?></td><td class="px-4 py-2.5 text-right font-mono text-xs font-semibold"><?= e(money($item['line_total'])) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3">
            <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Ara Toplam</dt><dd class="font-mono"><?= e(money($order['subtotal_net'])) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">KDV</dt><dd class="font-mono"><?= e(money($order['total_vat'])) ?></dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-1.5"><dt class="font-bold text-slate-900">Genel Toplam</dt><dd class="font-mono text-base font-bold"><?= e(money($order['grand_total'])) ?></dd></div>
            </dl>
        </div>
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">Bayi</h2>
            <div class="mt-2 text-xs text-slate-600"><div class="font-semibold text-slate-800"><?= e((string) ($company['title'] ?? '')) ?></div><div class="font-mono"><?= e((string) ($company['company_code'] ?? '')) ?></div><div><?= e((string) ($company['phone'] ?? '')) ?></div></div>
            <div class="mt-3 border-t border-slate-100 pt-2 text-[11px] text-slate-500"><div class="font-semibold text-slate-600">Sevkiyat</div><?= e((string) ($shipping['full_address'] ?? '-')) ?></div>
            <div class="mt-2 text-[11px] text-slate-500"><div class="font-semibold text-slate-600">Fatura</div><?= e((string) ($billing['full_address'] ?? '-')) ?></div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">Durum Geçmişi</h2>
            <ol class="mt-3 space-y-3">
                <?php foreach ($history as $h): ?>
                    <li class="flex gap-2"><span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-action"></span><div><div class="text-[11px] font-semibold text-slate-800"><?= e(order_status_label((string) $h['new_status'])) ?></div><div class="text-[10px] text-slate-400"><?= e(tr_date((string) $h['created_at'], true)) ?><?= !empty($h['full_name']) ? ' · ' . e((string) $h['full_name']) : '' ?></div><?php if (!empty($h['comment'])): ?><div class="text-[10px] text-slate-500"><?= e((string) $h['comment']) ?></div><?php endif; ?></div></li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
</div>
