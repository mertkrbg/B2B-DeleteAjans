<?php
$seller = $seller ?? [];
$company = $company ?? [];
?>
<div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
    <div>
        <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise" width="200" height="44" class="h-10 w-auto">
        <div class="mt-2 text-[11px] leading-relaxed text-slate-500">
            <div class="font-semibold text-slate-700"><?= e((string) ($seller['title'] ?? '')) ?></div>
            <div><?= e((string) ($seller['address'] ?? '')) ?></div>
            <div>V.D.: <?= e((string) ($seller['tax_office'] ?? '')) ?> · VKN: <span class="mono"><?= e((string) ($seller['tax_number'] ?? '')) ?></span></div>
            <div><?= e((string) ($seller['phone'] ?? '')) ?> · <?= e((string) ($seller['email'] ?? '')) ?></div>
        </div>
    </div>
    <div class="text-right">
        <div class="inline-block rounded-md bg-slate-900 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Proforma / Sipariş Formu</div>
        <table class="mt-3 ml-auto text-[11px]">
            <tr><td class="pr-3 text-slate-500">Sipariş No</td><td class="mono font-semibold text-right"><?= e((string) $order['order_number']) ?></td></tr>
            <tr><td class="pr-3 text-slate-500">Tarih</td><td class="text-right"><?= e(tr_date((string) $order['created_at'], true)) ?></td></tr>
            <tr><td class="pr-3 text-slate-500">Durum</td><td class="text-right font-semibold"><?= e(order_status_label((string) $order['status'])) ?></td></tr>
            <?php if (!empty($order['customer_po_number'])): ?><tr><td class="pr-3 text-slate-500">Müşteri PO</td><td class="mono text-right"><?= e((string) $order['customer_po_number']) ?></td></tr><?php endif; ?>
        </table>
    </div>
</div>

<div class="mt-4 grid grid-cols-2 gap-4 text-[11px]">
    <div class="rounded-lg border border-slate-200 p-3">
        <div class="mb-1 font-bold uppercase tracking-wide text-slate-500">Müşteri</div>
        <div class="font-semibold text-slate-800"><?= e((string) ($company['title'] ?? '')) ?></div>
        <div class="text-slate-500">V.D.: <?= e((string) ($company['tax_office'] ?? '')) ?> · VKN: <span class="mono"><?= e((string) ($company['tax_number'] ?? '')) ?></span></div>
        <div class="text-slate-500"><?= e((string) ($company['address'] ?? '')) ?></div>
        <div class="text-slate-500"><?= e((string) ($company['phone'] ?? '')) ?> · <?= e((string) ($company['email'] ?? '')) ?></div>
    </div>
    <div class="grid grid-cols-1 gap-2">
        <div class="rounded-lg border border-slate-200 p-3">
            <div class="mb-1 font-bold uppercase tracking-wide text-slate-500">Sevkiyat Adresi</div>
            <div class="font-semibold text-slate-800"><?= e((string) ($shipping['title'] ?? '-')) ?></div>
            <div class="text-slate-500"><?= e((string) ($shipping['full_address'] ?? '')) ?></div>
        </div>
        <div class="rounded-lg border border-slate-200 p-3">
            <div class="mb-1 font-bold uppercase tracking-wide text-slate-500">Fatura Adresi</div>
            <div class="font-semibold text-slate-800"><?= e((string) ($billing['title'] ?? '-')) ?></div>
            <div class="text-slate-500"><?= e((string) ($billing['full_address'] ?? '')) ?></div>
        </div>
    </div>
</div>

<table class="mt-5 w-full border-collapse text-[11px]">
    <thead>
        <tr class="bg-slate-900 text-white">
            <th class="px-2 py-2 text-left font-semibold">#</th>
            <th class="px-2 py-2 text-left font-semibold">Ürün / Hizmet</th>
            <th class="px-2 py-2 text-right font-semibold">Adet</th>
            <th class="px-2 py-2 text-right font-semibold">Birim (KDV Hariç)</th>
            <th class="px-2 py-2 text-right font-semibold">KDV</th>
            <th class="px-2 py-2 text-right font-semibold">Tutar</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
            <tr class="border-b border-slate-200">
                <td class="px-2 py-1.5 text-slate-500"><?= $i + 1 ?></td>
                <td class="px-2 py-1.5"><div class="font-medium text-slate-800"><?= e((string) $item['name_snapshot']) ?></div><div class="mono text-[10px] text-slate-400"><?= e((string) $item['sku_snapshot']) ?></div></td>
                <td class="px-2 py-1.5 text-right mono"><?= (int) $item['quantity'] ?></td>
                <td class="px-2 py-1.5 text-right mono"><?= e(money($item['unit_net_price'])) ?></td>
                <td class="px-2 py-1.5 text-right text-slate-500">%<?= e(rtrim(rtrim(number_format((float) $item['vat_rate'], 2, ',', '.'), '0'), ',')) ?></td>
                <td class="px-2 py-1.5 text-right mono font-semibold"><?= e(money($item['line_total'])) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="mt-4 flex flex-col gap-4 sm:flex-row sm:justify-between">
    <div class="max-w-xs text-[10px] leading-relaxed text-slate-500">
        <div class="mb-1 font-bold uppercase tracking-wide text-slate-500">Ödeme Bilgisi</div>
        <div><?= e(payment_method_label((string) $order['payment_method'])) ?><?= (int) $order['payment_term_days'] > 0 ? ' · ' . (int) $order['payment_term_days'] . ' gün vade' : '' ?></div>
        <?php if (!empty($seller['iban'])): ?><div class="mt-1"><?= e((string) $seller['bank']) ?> · IBAN: <span class="mono"><?= e((string) $seller['iban']) ?></span></div><?php endif; ?>
        <?php if (!empty($order['customer_note'])): ?><div class="mt-2"><span class="font-semibold text-slate-600">Not:</span> <?= e((string) $order['customer_note']) ?></div><?php endif; ?>
    </div>
    <table class="ml-auto w-full max-w-[300px] text-[11px]">
        <tr><td class="py-1 text-slate-500">Liste Toplamı</td><td class="py-1 text-right mono"><?= e(money($order['subtotal_list'])) ?></td></tr>
        <tr><td class="py-1 text-slate-500">İskonto</td><td class="py-1 text-right mono text-emerald-600">-<?= e(money($order['total_discount'])) ?></td></tr>
        <tr><td class="py-1 text-slate-500">Ara Toplam (KDV Hariç)</td><td class="py-1 text-right mono"><?= e(money($order['subtotal_net'])) ?></td></tr>
        <tr><td class="py-1 text-slate-500">KDV</td><td class="py-1 text-right mono"><?= e(money($order['total_vat'])) ?></td></tr>
        <tr class="border-t-2 border-slate-900"><td class="py-1.5 font-bold text-slate-900">Genel Toplam</td><td class="py-1.5 text-right mono text-sm font-bold text-slate-900"><?= e(money($order['grand_total'])) ?></td></tr>
    </table>
</div>

<div class="mt-6 border-t border-slate-200 pt-3 text-center text-[9px] text-slate-400">
    Bu belge proforma niteliğindedir, mal mukabili fatura yerine geçmez. <?= e((string) ($seller['title'] ?? '')) ?> · <?= e(date('Y')) ?>
</div>
