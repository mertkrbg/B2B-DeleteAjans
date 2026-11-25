<?php
$settings = $settings ?? [];
$rates = $rates ?? [];
$logs = $logs ?? [];
$entities = $entities ?? [];
$totalPages = $totalPages ?? 1;
$page = $page ?? 1;
$entityFilter = $entityFilter ?? '';
$actionFilter = $actionFilter ?? '';
$entityLabels = [
    'order' => 'Sipariş', 'company' => 'Bayi', 'payment_notification' => 'Ödeme Bildirimi',
    'currency_rates' => 'Döviz Kurları', 'system_settings' => 'Sistem Ayarları',
    'product' => 'Ürün', 'quote' => 'Teklif', 'user' => 'Kullanıcı',
];
$actionBadge = static function (string $action): string {
    $prefix = explode('.', $action)[0];
    return [
        'order' => 'bg-blue-50 text-blue-700 border-blue-200',
        'payment' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'settings' => 'bg-amber-50 text-amber-700 border-amber-200',
        'company' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'product' => 'bg-slate-100 text-slate-700 border-slate-200',
    ][$prefix] ?? 'bg-slate-100 text-slate-600 border-slate-200';
};
?>
<div class="mb-5">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Ayarlar & Denetim Logları</h1>
    <p class="mt-1 text-sm text-slate-500">Döviz kurlarını, KDV, kargo ve banka bilgilerini yönetin; tüm kritik işlemlerin denetim izini inceleyin.</p>
</div>

<div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="refresh-cw" class="h-4 w-4 text-action"></i> Döviz Kurları</h2>
                <p class="mt-1 text-xs text-slate-500">TCMB / manuel kurlar. USD &amp; EUR ürün fiyatlarına anında yansır.</p>
            </div>
        </div>
        <form action="<?= url('admin/ayarlar/kur') ?>" method="POST" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <?php foreach ($rates as $rate): ?>
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                    <div class="flex items-center justify-between">
                        <span class="flex items-center gap-2 font-mono text-sm font-bold text-slate-800"><i data-lucide="banknote" class="h-4 w-4 text-slate-400"></i> <?= e((string) $rate['currency_code']) ?></span>
                        <span class="text-[10px] text-slate-400">Güncelleme: <?= e(tr_date((string) $rate['updated_at'], true)) ?></span>
                    </div>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <div>
                            <label for="buy_<?= e((string) $rate['currency_code']) ?>" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Alış (₺)</label>
                            <input type="number" step="0.0001" min="0.0001" id="buy_<?= e((string) $rate['currency_code']) ?>" name="rates[<?= e((string) $rate['currency_code']) ?>][buying]" value="<?= e((string) $rate['buying_rate']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono" required>
                        </div>
                        <div>
                            <label for="sell_<?= e((string) $rate['currency_code']) ?>" class="mb-1 block text-[10px] font-semibold uppercase tracking-wide text-slate-500">Satış (₺)</label>
                            <input type="number" step="0.0001" min="0.0001" id="sell_<?= e((string) $rate['currency_code']) ?>" name="rates[<?= e((string) $rate['currency_code']) ?>][selling]" value="<?= e((string) $rate['selling_rate']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono" required>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($rates)): ?><p class="py-4 text-center text-sm text-slate-500">Döviz kuru tanımlı değil.</p><?php endif; ?>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="save" class="h-4 w-4"></i> Kurları Güncelle</button>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="settings-2" class="h-4 w-4 text-action"></i> Sistem Ayarları</h2>
        <p class="mt-1 text-xs text-slate-500">Firma bilgisi, vergi, kargo ve banka hesap bilgilerini düzenleyin.</p>
        <form action="<?= url('admin/ayarlar') ?>" method="POST" class="mt-4 space-y-3">
            <?= csrf_field() ?>
            <div>
                <label for="company_title" class="mb-1 block text-xs font-semibold text-slate-600">Firma Unvanı</label>
                <input type="text" id="company_title" name="company_title" value="<?= e($settings['company_title'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="support_email" class="mb-1 block text-xs font-semibold text-slate-600">Destek E-posta</label>
                    <input type="email" id="support_email" name="support_email" value="<?= e($settings['support_email'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="default_vat_rate" class="mb-1 block text-xs font-semibold text-slate-600">Varsayılan KDV (%)</label>
                    <input type="number" step="0.01" min="0" max="100" id="default_vat_rate" name="default_vat_rate" value="<?= e($settings['default_vat_rate'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="shipping_cost" class="mb-1 block text-xs font-semibold text-slate-600">Kargo Ücreti (₺)</label>
                    <input type="number" step="0.01" min="0" id="shipping_cost" name="shipping_cost" value="<?= e($settings['shipping_cost'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
                </div>
                <div>
                    <label for="bank_name" class="mb-1 block text-xs font-semibold text-slate-600">Banka Adı</label>
                    <input type="text" id="bank_name" name="bank_name" value="<?= e($settings['bank_name'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </div>
            </div>
            <div>
                <label for="bank_iban" class="mb-1 block text-xs font-semibold text-slate-600">Banka IBAN</label>
                <input type="text" id="bank_iban" name="bank_iban" value="<?= e($settings['bank_iban'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
            </div>
            <div>
                <label for="min_order_note" class="mb-1 block text-xs font-semibold text-slate-600">Minimum Sipariş Notu</label>
                <input type="text" id="min_order_note" name="min_order_note" value="<?= e($settings['min_order_note'] ?? '') ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="save" class="h-4 w-4"></i> Ayarları Kaydet</button>
        </form>
    </div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="shield-check" class="h-4 w-4 text-action"></i> Denetim Kayıtları</h2>
            <p class="text-xs text-slate-500">Toplam <?= (int) $total ?> kayıt · IP ve eski/yeni değerlerle değiştirilemez denetim izi</p>
        </div>
        <form action="<?= url('admin/ayarlar') ?>" method="GET" class="flex flex-wrap items-center gap-2">
            <input type="text" name="islem" value="<?= e($actionFilter) ?>" placeholder="İşlem (örn. payment)" class="w-36 rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs">
            <select name="varlik" class="rounded-lg border border-slate-300 px-2.5 py-1.5 text-xs">
                <option value="">Tüm varlıklar</option>
                <?php foreach ($entities as $ent): ?>
                    <?php $code = (string) $ent['entity_type']; ?>
                    <option value="<?= e($code) ?>" <?= $entityFilter === $code ? 'selected' : '' ?>><?= e($entityLabels[$code] ?? $code) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="rounded-lg bg-navy px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Filtrele</button>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                <tr><th class="px-4 py-2.5 font-semibold">Tarih</th><th class="px-3 py-2.5 font-semibold">Kullanıcı</th><th class="px-3 py-2.5 font-semibold">İşlem</th><th class="px-3 py-2.5 font-semibold">Varlık</th><th class="px-3 py-2.5 font-semibold">IP</th><th class="px-4 py-2.5 font-semibold">Değişim</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                <?php foreach ($logs as $log): ?>
                    <tr class="align-top hover:bg-slate-50/60">
                        <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600"><?= e(tr_date((string) $log['created_at'], true)) ?></td>
                        <td class="px-3 py-3 text-xs text-slate-700"><?= e((string) ($log['user_name'] ?? 'Sistem')) ?><?php if (!empty($log['company_title'])): ?><div class="text-[10px] text-slate-400"><?= e((string) $log['company_title']) ?></div><?php endif; ?></td>
                        <td class="px-3 py-3"><span class="inline-flex rounded-full border px-2 py-0.5 font-mono text-[10px] font-semibold <?= e($actionBadge((string) $log['action'])) ?>"><?= e((string) $log['action']) ?></span></td>
                        <td class="px-3 py-3 text-xs text-slate-600"><?= e($entityLabels[(string) $log['entity_type']] ?? (string) ($log['entity_type'] ?? '-')) ?><?php if (!empty($log['entity_id'])): ?> <span class="text-[10px] text-slate-400">#<?= (int) $log['entity_id'] ?></span><?php endif; ?></td>
                        <td class="px-3 py-3 font-mono text-[10px] text-slate-500"><?= e((string) ($log['ip_address'] ?? '-')) ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($log['old_values_json']) || !empty($log['new_values_json'])): ?>
                                <details class="group">
                                    <summary class="cursor-pointer text-[11px] font-semibold text-action hover:underline">Detay</summary>
                                    <div class="mt-1.5 space-y-1">
                                        <?php if (!empty($log['old_values_json'])): ?><pre class="max-w-[420px] overflow-x-auto rounded-lg bg-red-50 p-2 font-mono text-[10px] text-red-700"><?= e((string) $log['old_values_json']) ?></pre><?php endif; ?>
                                        <?php if (!empty($log['new_values_json'])): ?><pre class="max-w-[420px] overflow-x-auto rounded-lg bg-emerald-50 p-2 font-mono text-[10px] text-emerald-700"><?= e((string) $log['new_values_json']) ?></pre><?php endif; ?>
                                    </div>
                                </details>
                            <?php else: ?>
                                <span class="text-[10px] text-slate-400">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($logs)): ?><tr><td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">Denetim kaydı bulunamadı.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center justify-between border-t border-slate-100 px-4 py-3">
            <span class="text-xs text-slate-500">Sayfa <?= (int) $page ?> / <?= (int) $totalPages ?></span>
            <div class="flex gap-2">
                <?php $qs = static fn (int $p): string => url('admin/ayarlar?' . http_build_query(array_filter(['islem' => $actionFilter, 'varlik' => $entityFilter, 'sayfa' => $p]))); ?>
                <?php if ($page > 1): ?><a href="<?= $qs($page - 1) ?>" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Önceki</a><?php endif; ?>
                <?php if ($page < $totalPages): ?><a href="<?= $qs($page + 1) ?>" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Sonraki</a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
