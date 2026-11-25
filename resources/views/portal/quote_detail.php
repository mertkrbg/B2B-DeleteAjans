<?php
$status = (string) $quote['status'];
$canAccept = $status === 'offered';
$role = (string) ($authUser['role'] ?? '');
$isRequester = in_array($role, ['company_owner', 'purchasing_manager', 'buyer'], true);
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500">
    <a href="<?= url('portal/teklifler') ?>" class="hover:text-action">Tekliflerim</a>
    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
    <span class="font-mono font-medium text-slate-700"><?= e((string) $quote['quote_number']) ?></span>
</nav>

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= e((string) $quote['quote_number']) ?></h1>
            <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= e(quote_status_classes($status)) ?>"><?= e(quote_status_label($status)) ?></span>
        </div>
        <p class="mt-1 text-sm text-slate-500">Oluşturulma: <?= e(tr_date((string) $quote['created_at'])) ?><?php if (!empty($quote['valid_until'])): ?> · Geçerlilik: <?= e(tr_date((string) $quote['valid_until'])) ?><?php endif; ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php if ($canAccept && $isRequester): ?>
            <form action="<?= url('portal/teklif/' . $quote['quote_number'] . '/kabul') ?>" method="POST" onsubmit="return confirm('Teklifi kabul edip siparişe dönüştürmek istiyor musunuz?');">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"><i data-lucide="check-circle" class="h-4 w-4"></i> Kabul Et & Siparişe Dönüştür</button>
            </form>
        <?php endif; ?>
        <a href="<?= url('portal/teklifler') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="arrow-left" class="h-4 w-4"></i> Listeye Dön</a>
    </div>
</div>

<?php if ($status === 'requested' || $status === 'reviewing'): ?>
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800"><i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0"></i> Teklif talebiniz satış ekibimiz tarafından fiyatlandırılıyor. Karşı teklif hazır olduğunda burada görüntülenecektir.</div>
<?php elseif ($status === 'offered'): ?>
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800"><i data-lucide="badge-check" class="mt-0.5 h-4 w-4 shrink-0"></i> Size özel teklif fiyatları aşağıda listelenmiştir. Kabul ettiğinizde tek tıkla siparişe dönüştürülür ve fiyatlar kilitlenir.</div>
<?php elseif ($status === 'accepted'): ?>
    <div class="mb-4 flex items-start gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><i data-lucide="check-circle" class="mt-0.5 h-4 w-4 shrink-0"></i> Bu teklif siparişe dönüştürülmüştür. <a href="<?= url('portal/siparisler') ?>" class="font-semibold underline">Siparişlerime git</a></div>
<?php endif; ?>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Teklif Kalemleri</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[680px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2 font-semibold">Ürün</th>
                    <th class="px-3 py-2 text-right font-semibold">Adet</th>
                    <th class="px-3 py-2 text-right font-semibold">Güncel Net</th>
                    <th class="px-3 py-2 text-right font-semibold">Hedef Fiyat</th>
                    <th class="px-4 py-2 text-right font-semibold">Teklif Fiyatı</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="px-4 py-3"><div class="font-medium text-slate-800"><?= e((string) $item['name']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $item['sku']) ?></div></td>
                        <td class="px-3 py-3 text-right tabular-nums"><?= (int) $item['quantity'] ?></td>
                        <td class="px-3 py-3 text-right font-mono text-xs text-slate-600"><?= e(money($item['current_net_price'])) ?></td>
                        <td class="px-3 py-3 text-right font-mono text-xs text-slate-400"><?= $item['requested_price'] !== null ? e(money($item['requested_price'])) : '—' ?></td>
                        <td class="px-4 py-3 text-right font-mono font-semibold <?= $item['offered_price'] !== null ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $item['offered_price'] !== null ? e(money($item['offered_price'])) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="border-t border-slate-100 px-4 py-3">
        <dl class="ml-auto max-w-xs space-y-1.5 text-sm">
            <?php if ($quote['target_total'] !== null): ?><div class="flex justify-between"><dt class="text-slate-500">Hedef Toplam</dt><dd class="font-mono"><?= e(money($quote['target_total'])) ?></dd></div><?php endif; ?>
            <div class="flex justify-between border-t border-slate-100 pt-1.5"><dt class="font-bold text-slate-900">Teklif Toplamı</dt><dd class="font-mono text-base font-bold <?= $quote['offered_total'] !== null ? 'text-emerald-600' : 'text-slate-400' ?>"><?= $quote['offered_total'] !== null ? e(money($quote['offered_total'])) : '—' ?></dd></div>
        </dl>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Teklif Notunuz</h3>
        <p class="mt-2 text-sm text-slate-600"><?= !empty($quote['customer_note']) ? e((string) $quote['customer_note']) : '—' ?></p>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Satış Ekibi Notu</h3>
        <p class="mt-2 text-sm text-slate-600"><?= !empty($quote['admin_note']) ? e((string) $quote['admin_note']) : '—' ?></p>
    </div>
</div>
