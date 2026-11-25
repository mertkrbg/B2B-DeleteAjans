<?php
$transactions = $transactions ?? [];
$aging = $aging ?? ['not_due' => 0, 'd0_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_plus' => 0];
$filters = $filters ?? ['from' => '', 'to' => ''];
$balance = (float) ($company['current_balance'] ?? 0);
?>
<div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
    <div>
        <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise" width="200" height="44" class="h-10 w-auto">
        <div class="mt-2 text-[11px] leading-relaxed text-slate-500">
            <div class="font-semibold text-slate-700">B2B Enterprise Tedarik A.Ş.</div>
            <div>Organize Sanayi Bölgesi 1. Cadde No:1, Başakşehir / İstanbul</div>
            <div>V.D.: Başakşehir · VKN: 9876543210</div>
        </div>
    </div>
    <div class="text-right">
        <div class="inline-block rounded-md bg-slate-900 px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-white">Cari Hesap Ekstresi</div>
        <div class="mt-2 text-[11px] text-slate-500">
            <?php if ($filters['from'] !== '' || $filters['to'] !== ''): ?>
                Dönem: <?= e($filters['from'] !== '' ? tr_date($filters['from']) : 'Başlangıç') ?> — <?= e($filters['to'] !== '' ? tr_date($filters['to']) : 'Bugün') ?>
            <?php else: ?>
                Tüm Hareketler · <?= e(tr_date(date('Y-m-d'))) ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="mt-4 grid grid-cols-2 gap-4 text-[11px]">
    <div class="rounded-lg border border-slate-200 p-3">
        <div class="mb-1 font-bold uppercase tracking-wide text-slate-500">Müşteri</div>
        <div class="font-semibold text-slate-800"><?= e((string) ($company['title'] ?? '')) ?></div>
        <div class="text-slate-500">V.D.: <?= e((string) ($company['tax_office'] ?? '')) ?> · VKN: <span class="mono"><?= e((string) ($company['tax_number'] ?? '')) ?></span></div>
        <div class="text-slate-500"><?= e((string) ($company['address'] ?? '')) ?></div>
    </div>
    <div class="rounded-lg border border-slate-200 p-3">
        <div class="mb-1 font-bold uppercase tracking-wide text-slate-500">Özet</div>
        <div class="flex justify-between"><span class="text-slate-500">Güncel Bakiye</span><span class="mono font-semibold"><?= e(money($balance)) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Vadesi Geçmiş</span><span class="mono font-semibold text-red-600"><?= e(money(($aging['d0_30'] ?? 0) + ($aging['d31_60'] ?? 0) + ($aging['d61_90'] ?? 0) + ($aging['d90_plus'] ?? 0))) ?></span></div>
        <div class="flex justify-between"><span class="text-slate-500">Tanımlı Limit</span><span class="mono font-semibold"><?= e(money($company['credit_limit'] ?? 0)) ?></span></div>
    </div>
</div>

<div class="mt-5 overflow-x-auto">
<table class="w-full min-w-[560px] border-collapse text-[11px]">
    <thead>
        <tr class="bg-slate-900 text-white">
            <th class="px-2 py-2 text-left font-semibold">İşlem No</th>
            <th class="px-2 py-2 text-left font-semibold">Tarih</th>
            <th class="px-2 py-2 text-left font-semibold">Açıklama</th>
            <th class="px-2 py-2 text-right font-semibold">Borç</th>
            <th class="px-2 py-2 text-right font-semibold">Alacak</th>
            <th class="px-2 py-2 text-right font-semibold">Bakiye</th>
            <th class="px-2 py-2 text-left font-semibold">Vade</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr class="border-b border-slate-200">
                <td class="px-2 py-1.5 mono text-[10px]"><?= e((string) $t['transaction_no']) ?></td>
                <td class="px-2 py-1.5"><?= e(date('d.m.Y', strtotime((string) $t['created_at']))) ?></td>
                <td class="px-2 py-1.5"><?= e((string) $t['description']) ?></td>
                <td class="px-2 py-1.5 text-right mono"><?= (float) $t['debit_amount'] > 0 ? e(money($t['debit_amount'])) : '' ?></td>
                <td class="px-2 py-1.5 text-right mono"><?= (float) $t['credit_amount'] > 0 ? e(money($t['credit_amount'])) : '' ?></td>
                <td class="px-2 py-1.5 text-right mono font-semibold"><?= e(money($t['running_balance'])) ?></td>
                <td class="px-2 py-1.5"><?= $t['due_date'] !== null ? e(date('d.m.Y', strtotime((string) $t['due_date']))) : '—' ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($transactions)): ?><tr><td colspan="7" class="px-2 py-4 text-center text-slate-400">Hareket bulunmuyor.</td></tr><?php endif; ?>
    </tbody>
</table>
</div>

<div class="mt-4 rounded-lg border border-slate-200 p-3">
    <div class="mb-1 font-bold uppercase tracking-wide text-slate-500 text-[10px]">Vade Yaşlandırma</div>
    <div class="grid grid-cols-5 gap-2 text-[10px]">
        <div><div class="text-slate-500">Vadesi Gelmemiş</div><div class="mono font-semibold"><?= e(money($aging['not_due'])) ?></div></div>
        <div><div class="text-slate-500">0-30 Gün</div><div class="mono font-semibold"><?= e(money($aging['d0_30'])) ?></div></div>
        <div><div class="text-slate-500">31-60 Gün</div><div class="mono font-semibold"><?= e(money($aging['d31_60'])) ?></div></div>
        <div><div class="text-slate-500">61-90 Gün</div><div class="mono font-semibold"><?= e(money($aging['d61_90'])) ?></div></div>
        <div><div class="text-slate-500">90+ Gün</div><div class="mono font-semibold text-red-600"><?= e(money($aging['d90_plus'])) ?></div></div>
    </div>
</div>

<div class="mt-6 border-t border-slate-200 pt-3 text-center text-[9px] text-slate-400">Bu ekstre bilgilendirme amaçlıdır. B2B Enterprise Tedarik A.Ş. · <?= e(date('Y')) ?></div>
