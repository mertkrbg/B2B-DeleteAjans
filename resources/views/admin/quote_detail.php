<?php
$items = $items ?? [];
$status = (string) $quote['status'];
$locked = in_array($status, ['accepted', 'rejected'], true);
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500"><a href="<?= url('admin/teklifler') ?>" class="hover:text-action">Teklifler</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><span class="font-mono font-medium text-slate-700"><?= e((string) $quote['quote_number']) ?></span></nav>

<div class="mb-4">
    <div class="flex flex-wrap items-center gap-2">
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= e((string) $quote['quote_number']) ?></h1>
        <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= e(quote_status_classes($status)) ?>"><?= e(quote_status_label($status)) ?></span>
    </div>
    <p class="mt-1 text-sm text-slate-500"><?= e((string) ($company['title'] ?? '')) ?> · <?= e(tr_date((string) $quote['created_at'], true)) ?></p>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_300px]">
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Teklif Kalemleri</h2></div>
        <form action="<?= url('admin/teklifler/' . $quote['quote_number']) ?>" method="POST">
            <?= csrf_field() ?>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2 font-semibold">Ürün</th><th class="px-3 py-2 text-right font-semibold">Adet</th><th class="px-3 py-2 text-right font-semibold">Güncel Net</th><th class="px-3 py-2 text-right font-semibold">Hedef</th><th class="px-4 py-2 text-right font-semibold">Teklif Fiyatı</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td class="px-4 py-2.5"><div class="font-medium text-slate-800"><?= e((string) $it['name']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $it['sku']) ?></div></td>
                                <td class="px-3 py-2.5 text-right tabular-nums"><?= (int) $it['quantity'] ?></td>
                                <td class="px-3 py-2.5 text-right font-mono text-xs text-slate-600"><?= e(money($it['current_net_price'])) ?></td>
                                <td class="px-3 py-2.5 text-right font-mono text-xs text-slate-400"><?= $it['requested_price'] !== null ? e(money($it['requested_price'])) : '—' ?></td>
                                <td class="px-4 py-2.5 text-right"><input type="number" step="0.01" min="0.01" name="offered_price[<?= (int) $it['id'] ?>]" value="<?= $it['offered_price'] !== null ? e(number_format((float) $it['offered_price'], 2, '.', '')) : '' ?>" required <?= $locked ? 'disabled' : '' ?> class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-xs tabular-nums"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (!$locked): ?>
                <div class="grid grid-cols-1 gap-3 border-t border-slate-100 px-4 py-3 sm:grid-cols-2">
                    <div><label class="mb-1 block text-xs font-semibold text-slate-600">Geçerlilik Tarihi</label><input type="date" name="valid_until" value="<?= e((string) ($quote['valid_until'] ?? date('Y-m-d', strtotime('+14 days')))) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></div>
                    <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">Bayiye Not</label><textarea name="admin_note" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= e((string) ($quote['admin_note'] ?? '')) ?></textarea></div>
                </div>
                <div class="flex items-center justify-end gap-2 border-t border-slate-100 px-4 py-3">
                    <button type="submit" class="rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Teklifi Gönder / Güncelle</button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">Talep Bilgisi</h2>
            <dl class="mt-2 space-y-2 text-xs">
                <div class="flex justify-between"><dt class="text-slate-500">Hedef Toplam</dt><dd class="font-mono"><?= $quote['target_total'] !== null ? e(money($quote['target_total'])) : '—' ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Teklif Toplamı</dt><dd class="font-mono font-semibold text-emerald-600"><?= $quote['offered_total'] !== null ? e(money($quote['offered_total'])) : '—' ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">Geçerlilik</dt><dd><?= $quote['valid_until'] !== null ? e(tr_date((string) $quote['valid_until'])) : '—' ?></dd></div>
            </dl>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h3 class="text-xs font-bold uppercase tracking-wide text-slate-500">Bayi Notu</h3>
            <p class="mt-1 text-sm text-slate-600"><?= !empty($quote['customer_note']) ? e((string) $quote['customer_note']) : '—' ?></p>
        </div>
    </div>
</div>
