<?php
$statusCounts = $statusCounts ?? [];
$tabs = ['' => 'Tümü', 'pending' => 'Onay Bekleyen', 'active' => 'Aktif', 'suspended' => 'Askıda', 'rejected' => 'Reddedilen'];
$statusLabels = ['pending' => ['Onay Bekliyor', 'bg-amber-50 text-amber-700 border-amber-200'], 'active' => ['Aktif', 'bg-emerald-50 text-emerald-700 border-emerald-200'], 'suspended' => ['Askıda', 'bg-red-50 text-red-700 border-red-200'], 'rejected' => ['Reddedildi', 'bg-slate-100 text-slate-600 border-slate-200']];
?>
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Bayi Yönetimi</h1>
        <p class="mt-1 text-sm text-slate-500">Bayi başvurularını onaylayın, risk limiti ve fiyat grubu atamalarını yönetin.</p>
    </div>
    <form action="<?= url('admin/bayiler') ?>" method="GET" class="flex gap-2">
        <input type="search" name="q" value="<?= e($q ?? '') ?>" placeholder="Ünvan / kod / VKN" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm sm:w-56">
        <button type="submit" class="rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Ara</button>
    </form>
</div>

<div class="mb-4 flex gap-1.5 overflow-x-auto pb-1 no-scrollbar">
    <?php foreach ($tabs as $key => $label): ?>
        <?php $count = $key === '' ? ($statusCounts['all'] ?? 0) : ($statusCounts[$key] ?? 0); ?>
        <a href="<?= url('admin/bayiler' . ($key !== '' ? '?durum=' . $key : '')) ?>" class="shrink-0 rounded-xl border px-3 py-1.5 text-xs font-semibold <?= $statusFilter === $key ? 'border-action bg-action text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' ?>"><?= e($label) ?> <span class="opacity-70">(<?= (int) $count ?>)</span></a>
    <?php endforeach; ?>
</div>

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-2.5 font-semibold">Bayi</th>
                    <th class="px-3 py-2.5 font-semibold">Seviye</th>
                    <th class="px-3 py-2.5 font-semibold">Fiyat Listesi</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Risk Limiti</th>
                    <th class="px-3 py-2.5 text-right font-semibold">Cari Bakiye</th>
                    <th class="px-3 py-2.5 font-semibold">Durum</th>
                    <th class="px-4 py-2.5"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($companies as $c): ?>
                    <?php $sl = $statusLabels[$c['status']] ?? ['—', 'bg-slate-100 text-slate-600 border-slate-200']; ?>
                    <tr class="hover:bg-slate-50/60">
                        <td class="px-4 py-3"><a href="<?= url('admin/bayiler/' . $c['id']) ?>" class="font-semibold text-slate-800 hover:text-action"><?= e((string) $c['title']) ?></a><div class="font-mono text-[11px] text-slate-500"><?= e((string) $c['company_code']) ?> · <?= e((string) ($c['city'] ?? '')) ?></div></td>
                        <td class="px-3 py-3"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600"><?= e(tier_label((string) $c['tier_level'])) ?></span></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) ($c['price_list_name'] ?? 'Standart')) ?></td>
                        <td class="px-3 py-3 text-right font-mono text-xs text-slate-700"><?= e(money($c['credit_limit'])) ?></td>
                        <td class="px-3 py-3 text-right font-mono text-xs font-semibold <?= (float) $c['current_balance'] > (float) $c['credit_limit'] ? 'text-red-600' : 'text-slate-800' ?>"><?= e(money($c['current_balance'])) ?></td>
                        <td class="px-3 py-3"><span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e($sl[1]) ?>"><?= e($sl[0]) ?></span></td>
                        <td class="px-4 py-3 text-right"><a href="<?= url('admin/bayiler/' . $c['id']) ?>" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50">Yönet <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i></a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($companies)): ?><tr><td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">Kayıt bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
