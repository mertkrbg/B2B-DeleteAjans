<?php
$payments = $payments ?? [];
$companies = $companies ?? [];
$recentTransactions = $recentTransactions ?? [];
$counts = $counts ?? ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'rejected' => 0];
$kpi = $kpi ?? ['total_receivable' => 0, 'total_overdue' => 0, 'total_limit' => 0];
$tabs = ['' => 'Tümü', 'pending' => 'Onay Bekleyen', 'confirmed' => 'Onaylanan', 'rejected' => 'Reddedilen'];
$paymentStatus = [
    'pending'   => ['label' => 'Onay Bekliyor', 'class' => 'bg-amber-50 text-amber-700 border-amber-200'],
    'confirmed' => ['label' => 'Onaylandı', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200'],
    'rejected'  => ['label' => 'Reddedildi', 'class' => 'bg-red-50 text-red-700 border-red-200'],
];
$methodLabels = ['bank_transfer' => 'Havale/EFT', 'credit_card' => 'Kredi Kartı', 'cheque' => 'Çek/Senet', 'other' => 'Diğer'];
?>
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Finans & Tahsilat</h1>
        <p class="mt-1 text-sm text-slate-500">Gelen dekont bildirimlerini onaylayın, manuel tahsilat işleyin ve cari risk durumunu izleyin.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= url('admin/siparisler') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="clipboard-list" class="h-4 w-4"></i> Siparişler</a>
        <a href="#manuel-tahsilat" class="inline-flex items-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="plus" class="h-4 w-4"></i> Manuel Tahsilat</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Toplam Cari Alacak</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-action"><i data-lucide="wallet" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= e(money($kpi['total_receivable'])) ?></div>
        <div class="mt-1 text-xs text-slate-500">Aktif bayilerin açık bakiyesi</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vadesi Geçmiş</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-red-600"><i data-lucide="alert-triangle" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-red-600"><?= e(money($kpi['total_overdue'])) ?></div>
        <div class="mt-1 text-xs text-slate-500">Takip gerektiren gecikmiş bakiye</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Onay Bekleyen Dekont</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber"><i data-lucide="receipt" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= (int) $counts['pending'] ?></div>
        <div class="mt-1 text-xs text-slate-500"><?= (int) $counts['all'] ?> toplam bildirim</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Bu Ay Tahsilat</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i data-lucide="trending-up" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-emerald-700"><?= e(money($collectedThisMonth)) ?></div>
        <div class="mt-1 text-xs text-slate-500">Onaylanan tahsilatlar</div>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="xl:col-span-2">
        <div class="mb-3 flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
            <?php foreach ($tabs as $key => $label): ?>
                <a href="<?= url('admin/finans' . ($key !== '' ? '?durum=' . $key : '')) ?>" class="shrink-0 rounded-xl border px-3 py-1.5 text-xs font-semibold <?= $statusFilter === $key ? 'border-action bg-action text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>"><?= e($label) ?><?= $key === 'pending' && (int) $counts['pending'] > 0 ? ' (' . (int) $counts['pending'] . ')' : '' ?></a>
            <?php endforeach; ?>
        </div>
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Bildirim</th>
                            <th class="px-3 py-2.5 font-semibold">Bayi</th>
                            <th class="px-3 py-2.5 font-semibold">Yöntem</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Tutar</th>
                            <th class="px-3 py-2.5 font-semibold">Durum</th>
                            <th class="px-4 py-2.5 text-right font-semibold">Aksiyon</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($payments as $p): ?>
                            <?php $st = $paymentStatus[(string) $p['status']] ?? $paymentStatus['pending']; ?>
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-4 py-3">
                                    <div class="font-mono text-xs font-semibold text-slate-800">#<?= (int) $p['id'] ?></div>
                                    <div class="text-[10px] text-slate-400"><?= e(tr_date((string) $p['created_at'], true)) ?></div>
                                    <?php if (!empty($p['reference_no'])): ?><div class="mt-0.5 text-[10px] text-slate-500">Ref: <?= e((string) $p['reference_no']) ?></div><?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-600">
                                    <div class="font-semibold text-slate-800"><?= e((string) $p['company_title']) ?></div>
                                    <div class="text-[10px] text-slate-400"><?= e((string) $p['company_code']) ?></div>
                                </td>
                                <td class="px-3 py-3 text-xs text-slate-600">
                                    <?= e($methodLabels[(string) $p['payment_method']] ?? (string) $p['payment_method']) ?>
                                    <?php if (!empty($p['bank_name'])): ?><div class="text-[10px] text-slate-400"><?= e((string) $p['bank_name']) ?></div><?php endif; ?>
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-xs font-semibold text-slate-900"><?= e(money($p['amount'])) ?></td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e($st['class']) ?>"><?= e($st['label']) ?></span>
                                    <?php if (!empty($p['receipt_file'])): ?><a href="<?= url('admin/finans/' . (int) $p['id'] . '/dekont') ?>" target="_blank" class="mt-1 inline-flex items-center gap-1 text-[10px] font-semibold text-action hover:underline"><i data-lucide="paperclip" class="h-3 w-3"></i> Dekont</a><?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <?php if ($p['status'] === 'pending'): ?>
                                        <div class="flex items-center justify-end gap-1.5">
                                            <form action="<?= url('admin/finans/' . (int) $p['id'] . '/onayla') ?>" method="POST" onsubmit="return confirm('Bu ödemeyi onaylayıp cari bakiyeden düşmek istiyor musunuz?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-emerald-700"><i data-lucide="check" class="h-3.5 w-3.5"></i> Onayla</button>
                                            </form>
                                            <form action="<?= url('admin/finans/' . (int) $p['id'] . '/reddet') ?>" method="POST" onsubmit="return confirm('Bu ödeme bildirimini reddetmek istiyor musunuz?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-3.5 w-3.5"></i> Reddet</button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-[10px] text-slate-400"><?= e((string) ($p['reviewer_name'] ?? 'İşlendi')) ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?><tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Ödeme bildirimi bulunamadı.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" id="manuel-tahsilat">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="banknote" class="h-4 w-4 text-action"></i> Manuel Tahsilat</h2>
        <p class="mt-1 text-xs text-slate-500">Nakit, havale veya çek tahsilatını doğrudan cari hesaba işleyin.</p>
        <form action="<?= url('admin/finans/tahsilat') ?>" method="POST" class="mt-3 space-y-3">
            <?= csrf_field() ?>
            <div>
                <label for="company_id" class="mb-1 block text-xs font-semibold text-slate-600">Bayi</label>
                <select id="company_id" name="company_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Bayi seçiniz</option>
                    <?php foreach ($companies as $c): ?>
                        <option value="<?= (int) $c['id'] ?>"><?= e((string) $c['company_code'] . ' - ' . (string) $c['title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="amount" class="mb-1 block text-xs font-semibold text-slate-600">Tutar (₺)</label>
                    <input type="number" step="0.01" min="0.01" id="amount" name="amount" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label for="payment_method" class="mb-1 block text-xs font-semibold text-slate-600">Yöntem</label>
                    <select id="payment_method" name="payment_method" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="bank_transfer">Havale/EFT</option>
                        <option value="credit_card">Kredi Kartı</option>
                        <option value="cheque">Çek/Senet</option>
                        <option value="other">Diğer</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="reference_no" class="mb-1 block text-xs font-semibold text-slate-600">Referans / İşlem No</label>
                <input type="text" id="reference_no" name="reference_no" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="description" class="mb-1 block text-xs font-semibold text-slate-600">Açıklama</label>
                <input type="text" id="description" name="description" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" placeholder="Örn: Elden tahsilat">
            </div>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="save" class="h-4 w-4"></i> Tahsilatı Kaydet</button>
        </form>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
        <div class="border-b border-slate-100 px-4 py-3">
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="shield-alert" class="h-4 w-4 text-red-500"></i> Cari Risk Raporu</h2>
            <p class="text-xs text-slate-500">Kullanılabilir limit = Risk limiti − (Güncel bakiye + Vadesi geçmiş)</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2.5 font-semibold">Bayi</th><th class="px-3 py-2.5 text-right font-semibold">Risk Limiti</th><th class="px-3 py-2.5 text-right font-semibold">Güncel Bakiye</th><th class="px-3 py-2.5 text-right font-semibold">Kullanılabilir</th><th class="px-4 py-2.5 font-semibold">Durum</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($companies as $c): ?>
                        <?php
                        $overdue = (float) $c['overdue_balance'];
                        $available = (float) $c['available_limit'];
                        $risk = $overdue > 0 || $available < 0;
                        ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 text-xs">
                                <div class="font-semibold text-slate-800"><?= e((string) $c['title']) ?></div>
                                <div class="text-[10px] text-slate-400"><?= e((string) $c['company_code']) ?> · <?= e(tier_label((string) $c['tier_level'])) ?> · <?= (int) $c['payment_term_days'] ?> gün vade</div>
                            </td>
                            <td class="px-3 py-3 text-right font-mono text-xs text-slate-600"><?= e(money($c['credit_limit'])) ?></td>
                            <td class="px-3 py-3 text-right font-mono text-xs font-semibold text-slate-900"><?= e(money($c['current_balance'])) ?></td>
                            <td class="px-3 py-3 text-right font-mono text-xs font-semibold <?= $available < 0 ? 'text-red-600' : 'text-emerald-700' ?>"><?= e(money($available)) ?></td>
                            <td class="px-4 py-3">
                                <?php if ($c['status'] === 'suspended'): ?><span class="inline-flex rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600">Askıda</span>
                                <?php elseif ($risk): ?><span class="inline-flex rounded-full border border-red-200 bg-red-50 px-2 py-0.5 text-[10px] font-semibold text-red-700">Riskli</span>
                                <?php else: ?><span class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">Normal</span><?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($companies)): ?><tr><td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">Kayıtlı bayi bulunamadı.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-4 py-3"><h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="history" class="h-4 w-4 text-action"></i> Son Cari Hareketler</h2></div>
        <div class="divide-y divide-slate-100">
            <?php foreach ($recentTransactions as $t): ?>
                <div class="px-4 py-3">
                    <div class="flex items-center justify-between gap-2">
                        <span class="truncate text-xs font-semibold text-slate-800"><?= e((string) $t['company_title']) ?></span>
                        <?php if ((float) $t['debit_amount'] > 0): ?>
                            <span class="shrink-0 font-mono text-xs font-semibold text-red-600">-<?= e(money($t['debit_amount'])) ?></span>
                        <?php else: ?>
                            <span class="shrink-0 font-mono text-xs font-semibold text-emerald-700">+<?= e(money($t['credit_amount'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-0.5 truncate text-[10px] text-slate-500"><?= e((string) $t['description']) ?></div>
                    <div class="mt-0.5 flex items-center justify-between text-[10px] text-slate-400">
                        <span><?= e(tr_date((string) $t['created_at'], true)) ?></span>
                        <span class="font-mono">Bakiye: <?= e(money($t['running_balance'])) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($recentTransactions)): ?><div class="px-4 py-10 text-center text-sm text-slate-500">Hareket bulunamadı.</div><?php endif; ?>
        </div>
    </div>
</div>
