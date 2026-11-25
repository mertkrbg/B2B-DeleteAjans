<?php
$transactions = $transactions ?? [];
$aging = $aging ?? ['not_due' => 0, 'd0_30' => 0, 'd31_60' => 0, 'd61_90' => 0, 'd90_plus' => 0];
$payments = $payments ?? [];
$filters = $filters ?? ['from' => '', 'to' => ''];
$statusMap = [
    'pending' => ['Onay Bekliyor', 'bg-amber-50 text-amber-700 border-amber-200'],
    'confirmed' => ['Onaylandı', 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'rejected' => ['Reddedildi', 'bg-red-50 text-red-700 border-red-200'],
];
$query = http_build_query(array_filter(['baslangic' => $filters['from'], 'bitis' => $filters['to']]));
?>
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Cari Hesap & Faturalar</h1>
        <p class="mt-1 text-sm text-slate-500">Borç/alacak ekstrenizi, vade yaşlandırmayı ve ödeme bildirimlerinizi görüntüleyin.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= url('portal/cari-hesap/csv' . ($query !== '' ? '?' . $query : '')) ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="download" class="h-4 w-4"></i> CSV</a>
        <a href="<?= url('portal/cari-hesap/yazdir' . ($query !== '' ? '?' . $query : '')) ?>" target="_blank" class="inline-flex items-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="printer" class="h-4 w-4"></i> Ekstre Yazdır</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Güncel Bakiye</div>
        <div class="mt-2 font-mono text-2xl font-bold text-slate-900"><?= e(money($credit['balance'] ?? 0)) ?></div>
        <div class="mt-1 text-xs text-slate-500">Açık hesap borcu</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vadesi Geçmiş</div>
        <div class="mt-2 font-mono text-2xl font-bold <?= ($credit['overdue'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' ?>"><?= e(money($credit['overdue'] ?? 0)) ?></div>
        <div class="mt-1 text-xs text-slate-500">Gecikmiş tutar</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kullanılabilir Limit</div>
        <div class="mt-2 font-mono text-2xl font-bold <?= ($credit['available'] ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= e(money($credit['available'] ?? 0)) ?></div>
        <div class="mt-1 text-xs text-slate-500">Tanımlı limit: <?= e(money($credit['limit'] ?? 0)) ?></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vade</div>
        <div class="mt-2 font-mono text-2xl font-bold text-slate-900"><?= (int) ($credit['term_days'] ?? 0) ?> gün</div>
        <div class="mt-1 text-xs text-slate-500">Ödeme vadesi</div>
    </div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
    <h2 class="text-sm font-bold text-slate-800">Vade Yaşlandırma</h2>
    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-5">
        <?php foreach ([['Vadesi Gelmemiş', $aging['not_due'], 'text-slate-700'], ['0-30 Gün', $aging['d0_30'], 'text-emerald-600'], ['31-60 Gün', $aging['d31_60'], 'text-amber-600'], ['61-90 Gün', $aging['d61_90'], 'text-orange-600'], ['90+ Gün', $aging['d90_plus'], 'text-red-600']] as [$label, $value, $tone]): ?>
            <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                <div class="text-[11px] text-slate-500"><?= e($label) ?></div>
                <div class="mt-1 font-mono text-sm font-bold <?= e($tone) ?>"><?= e(money($value)) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-2 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <h2 class="text-sm font-bold text-slate-800">Hesap Ekstresi</h2>
        <form action="<?= url('portal/cari-hesap') ?>" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="date" name="baslangic" value="<?= e($filters['from']) ?>" class="rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
            <span class="text-xs text-slate-400">—</span>
            <input type="date" name="bitis" value="<?= e($filters['to']) ?>" class="rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
            <button type="submit" class="rounded-lg bg-navy px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Filtrele</button>
            <?php if ($filters['from'] !== '' || $filters['to'] !== ''): ?><a href="<?= url('portal/cari-hesap') ?>" class="text-xs font-semibold text-action">Temizle</a><?php endif; ?>
        </form>
    </div>
    <?php if (empty($transactions)): ?>
        <div class="px-5 py-10 text-center text-sm text-slate-500">Seçilen aralıkta cari hareket bulunmuyor.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[820px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2 font-semibold">İşlem No</th>
                        <th class="px-3 py-2 font-semibold">Tarih</th>
                        <th class="px-3 py-2 font-semibold">Açıklama</th>
                        <th class="px-3 py-2 text-right font-semibold">Borç</th>
                        <th class="px-3 py-2 text-right font-semibold">Alacak</th>
                        <th class="px-3 py-2 text-right font-semibold">Bakiye</th>
                        <th class="px-3 py-2 font-semibold">Vade</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($transactions as $t): ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-2.5 font-mono text-[11px] text-slate-700"><?= e((string) $t['transaction_no']) ?></td>
                            <td class="px-3 py-2.5 text-xs text-slate-600"><?= e(tr_date((string) $t['created_at'])) ?></td>
                            <td class="px-3 py-2.5 text-slate-700"><?= e((string) $t['description']) ?></td>
                            <td class="px-3 py-2.5 text-right font-mono text-xs <?= (float) $t['debit_amount'] > 0 ? 'text-red-600' : 'text-slate-300' ?>"><?= (float) $t['debit_amount'] > 0 ? e(money($t['debit_amount'])) : '—' ?></td>
                            <td class="px-3 py-2.5 text-right font-mono text-xs <?= (float) $t['credit_amount'] > 0 ? 'text-emerald-600' : 'text-slate-300' ?>"><?= (float) $t['credit_amount'] > 0 ? e(money($t['credit_amount'])) : '—' ?></td>
                            <td class="px-3 py-2.5 text-right font-mono text-xs font-semibold text-slate-800"><?= e(money($t['running_balance'])) ?></td>
                            <td class="px-3 py-2.5 text-xs text-slate-500"><?= $t['due_date'] !== null ? e(tr_date((string) $t['due_date'])) : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="banknote" class="h-4 w-4 text-action"></i> Ödeme / Dekont Bildirimi</h2>
        <p class="mt-1 text-xs text-slate-500">Havale/EFT dekontunuzu yükleyin veya ödeme bilgisini iletin. Finans onayı sonrası bakiyenize yansır.</p>
        <form action="<?= url('portal/cari-hesap/odeme') ?>" method="POST" enctype="multipart/form-data" class="mt-3 space-y-3">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="amount" class="mb-1 block text-xs font-semibold text-slate-600">Tutar (₺)</label>
                    <input type="number" step="0.01" min="1" id="amount" name="amount" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm tabular-nums">
                </div>
                <div>
                    <label for="payment_method" class="mb-1 block text-xs font-semibold text-slate-600">Ödeme Yöntemi</label>
                    <select id="payment_method" name="payment_method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="bank_transfer">Havale / EFT</option>
                        <option value="credit_card">Kredi Kartı</option>
                        <option value="cheque">Çek / Senet</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
                <div>
                    <label for="bank_name" class="mb-1 block text-xs font-semibold text-slate-600">Banka</label>
                    <input type="text" id="bank_name" name="bank_name" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="reference_no" class="mb-1 block text-xs font-semibold text-slate-600">İşlem / Dekont No</label>
                    <input type="text" id="reference_no" name="reference_no" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label for="receipt" class="mb-1 block text-xs font-semibold text-slate-600">Dekont (PDF/JPG/PNG)</label>
                <input type="file" id="receipt" name="receipt" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
            </div>
            <div>
                <label for="note" class="mb-1 block text-xs font-semibold text-slate-600">Not</label>
                <input type="text" id="note" name="note" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="w-full rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Ödeme Bildir</button>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Ödeme Bildirimlerim</h2>
        <?php if (empty($payments)): ?>
            <p class="mt-3 text-sm text-slate-500">Henüz ödeme bildiriminiz bulunmuyor.</p>
        <?php else: ?>
            <div class="mt-3 space-y-2">
                <?php foreach ($payments as $p): ?>
                    <?php $sm = $statusMap[$p['status']] ?? ['—', 'bg-slate-100 text-slate-600 border-slate-200']; ?>
                    <div class="rounded-xl border border-slate-200 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-mono text-sm font-semibold text-slate-800"><?= e(money($p['amount'])) ?></span>
                            <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e($sm[1]) ?>"><?= e($sm[0]) ?></span>
                        </div>
                        <div class="mt-1 text-[11px] text-slate-500"><?= e((string) ($p['bank_name'] ?? '-')) ?><?= !empty($p['reference_no']) ? ' · ' . e((string) $p['reference_no']) : '' ?> · <?= e(tr_date((string) $p['created_at'])) ?></div>
                        <?php if (!empty($p['note'])): ?><div class="mt-1 text-[11px] text-slate-500"><?= e((string) $p['note']) ?></div><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
