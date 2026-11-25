<?php
$status = (string) $company['status'];
$risk = $risk ?? [];
$statusLabel = ['pending' => ['Onay Bekliyor', 'bg-amber-50 text-amber-700 border-amber-200'], 'active' => ['Aktif', 'bg-emerald-50 text-emerald-700 border-emerald-200'], 'suspended' => ['Askıda', 'bg-red-50 text-red-700 border-red-200'], 'rejected' => ['Reddedildi', 'bg-slate-100 text-slate-600 border-slate-200']][$status] ?? ['—', 'bg-slate-100 text-slate-600 border-slate-200'];
$tiers = ['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum', 'vip' => 'VIP'];
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500"><a href="<?= url('admin/bayiler') ?>" class="hover:text-action">Bayiler</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><span class="font-mono font-medium text-slate-700"><?= e((string) $company['company_code']) ?></span></nav>

<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
    <div>
        <div class="flex flex-wrap items-center gap-2">
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= e((string) $company['title']) ?></h1>
            <span class="inline-flex rounded-full border px-2.5 py-0.5 text-[11px] font-semibold <?= e($statusLabel[1]) ?>"><?= e($statusLabel[0]) ?></span>
        </div>
        <p class="mt-1 text-sm text-slate-500"><?= e((string) $company['company_code']) ?> · <?= e(tier_label((string) $company['tier_level'])) ?> · VKN <?= e((string) $company['tax_number']) ?></p>
    </div>
    <div class="flex flex-wrap gap-2">
        <?php if ($status === 'pending'): ?>
            <form action="<?= url('admin/bayiler/' . $company['id'] . '/onayla') ?>" method="POST"><?= csrf_field() ?><button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-3 py-2 text-sm font-semibold text-white hover:bg-emerald-700"><i data-lucide="check" class="h-4 w-4"></i> Onayla</button></form>
            <form action="<?= url('admin/bayiler/' . $company['id'] . '/reddet') ?>" method="POST" onsubmit="return confirm('Başvuruyu reddetmek istiyor musunuz?');"><?= csrf_field() ?><button type="submit" class="inline-flex items-center gap-2 rounded-xl border border-red-300 bg-white px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"><i data-lucide="x" class="h-4 w-4"></i> Reddet</button></form>
        <?php endif; ?>
        <a href="<?= url('admin/bayiler') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="arrow-left" class="h-4 w-4"></i> Liste</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Risk Limiti</div><div class="mt-2 font-mono text-xl font-bold text-slate-900"><?= e(money($risk['limit'] ?? 0)) ?></div></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Cari Bakiye</div><div class="mt-2 font-mono text-xl font-bold text-slate-900"><?= e(money($risk['balance'] ?? 0)) ?></div></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kullanılabilir</div><div class="mt-2 font-mono text-xl font-bold <?= ($risk['available'] ?? 0) < 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= e(money($risk['available'] ?? 0)) ?></div></div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wide text-slate-500">Vadesi Geçmiş</div><div class="mt-2 font-mono text-xl font-bold <?= ($risk['overdue'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-900' ?>"><?= e(money($risk['overdue'] ?? 0)) ?></div></div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
        <h2 class="text-sm font-bold text-slate-800">Bayi Ayarları</h2>
        <form action="<?= url('admin/bayiler/' . $company['id']) ?>" method="POST" class="mt-4 space-y-4">
            <?= csrf_field() ?>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Risk Limiti (₺)</label><input type="number" step="0.01" min="0" name="credit_limit" value="<?= e(number_format((float) $company['credit_limit'], 2, '.', '')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Vade (gün)</label><input type="number" min="0" name="payment_term_days" value="<?= (int) $company['payment_term_days'] ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Bayi Seviyesi</label><select name="tier_level" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><?php foreach ($tiers as $tk => $tl): ?><option value="<?= e($tk) ?>" <?= $company['tier_level'] === $tk ? 'selected' : '' ?>><?= e($tl) ?></option><?php endforeach; ?></select></div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Cari İskonto (%)</label><input type="number" step="0.01" min="0" max="100" name="base_discount_rate" value="<?= e(number_format((float) $company['base_discount_rate'], 2, '.', '')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Fiyat Listesi</label><select name="price_list_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><option value="0">Standart Liste</option><?php foreach ($priceLists as $pl): ?><option value="<?= (int) $pl['id'] ?>" <?= (int) $company['price_list_id'] === (int) $pl['id'] ? 'selected' : '' ?>><?= e((string) $pl['name']) ?></option><?php endforeach; ?></select></div>
                <div><label class="mb-1 block text-xs font-semibold text-slate-600">Durum</label><select name="status" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Onay Bekliyor</option><option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option><option value="suspended" <?= $status === 'suspended' ? 'selected' : '' ?>>Askıda</option><option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Reddedildi</option></select></div>
            </div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Notlar</label><textarea name="notes" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><?= e((string) ($company['notes'] ?? '')) ?></textarea></div>
            <button type="submit" class="rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Değişiklikleri Kaydet</button>
        </form>
    </div>

    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">Firma Bilgileri</h2>
            <dl class="mt-3 space-y-2 text-xs">
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Vergi Dairesi</dt><dd class="text-right font-medium text-slate-800"><?= e((string) $company['tax_office']) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">VKN</dt><dd class="text-right font-mono"><?= e((string) $company['tax_number']) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">MERSİS</dt><dd class="text-right font-mono"><?= e((string) ($company['mersis_no'] ?? '-')) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Telefon</dt><dd class="text-right"><?= e((string) ($company['phone'] ?? '-')) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">E-posta</dt><dd class="text-right"><?= e((string) ($company['email'] ?? '-')) ?></dd></div>
                <div class="flex justify-between gap-3"><dt class="text-slate-500">Adres</dt><dd class="text-right"><?= e((string) ($company['city'] ?? '')) ?> / <?= e((string) ($company['district'] ?? '')) ?></dd></div>
            </dl>
            <div class="mt-3 border-t border-slate-100 pt-3">
                <?php if (!empty($company['tax_plate_file'])): ?>
                    <a href="<?= url('admin/bayiler/' . $company['id'] . '/vergi-levhasi') ?>" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="file-text" class="h-4 w-4"></i> Vergi Levhasını Görüntüle</a>
                <?php else: ?>
                    <span class="text-xs text-slate-400">Vergi levhası yüklenmemiş.</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">Yetkili Kullanıcılar</h2>
            <div class="mt-3 space-y-2">
                <?php foreach ($users as $u): ?>
                    <div class="flex items-center justify-between gap-2 text-xs"><span class="truncate text-slate-700"><?= e((string) $u['full_name']) ?><span class="block text-[10px] text-slate-400"><?= e(role_label((string) $u['role'])) ?></span></span><span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-semibold <?= (int) $u['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $u['is_active'] ? 'Aktif' : 'Pasif' ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Son Siparişler</h2></div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[640px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2 font-semibold">Sipariş</th><th class="px-3 py-2 font-semibold">Tarih</th><th class="px-3 py-2 font-semibold">Durum</th><th class="px-4 py-2 text-right font-semibold">Tutar</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($orders as $o): ?>
                    <tr><td class="px-4 py-2.5 font-mono text-xs text-slate-800"><?= e((string) $o['order_number']) ?></td><td class="px-3 py-2.5 text-xs text-slate-600"><?= e(tr_date((string) $o['created_at'])) ?></td><td class="px-3 py-2.5"><span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e(order_status_classes((string) $o['status'])) ?>"><?= e(order_status_label((string) $o['status'])) ?></span></td><td class="px-4 py-2.5 text-right font-mono text-xs font-semibold"><?= e(money($o['grand_total'])) ?></td></tr>
                <?php endforeach; ?>
                <?php if (empty($orders)): ?><tr><td colspan="4" class="px-4 py-6 text-center text-xs text-slate-400">Sipariş yok.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
