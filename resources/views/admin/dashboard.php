<?php $kpi = $kpi ?? []; ?>
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Yönetim Kokpiti</h1>
        <p class="mt-1 text-sm text-slate-500">B2B operasyonunuzun anlık durumu ve kritik aksiyonlar.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="<?= url('admin/bayiler?durum=pending') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="building-2" class="h-4 w-4"></i> Bayi Başvuruları</a>
        <a href="<?= url('admin/siparisler') ?>" class="inline-flex items-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="clipboard-list" class="h-4 w-4"></i> Siparişler</a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Toplam B2B Ciro</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i data-lucide="trending-up" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= e(money($kpi['totalRevenue'] ?? 0)) ?></div>
        <div class="mt-1 text-xs text-slate-500"><?= (int) ($kpi['orderCount'] ?? 0) ?> toplam sipariş</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Onay Bekleyen Sipariş</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber"><i data-lucide="clock" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= (int) ($kpi['pendingOrders'] ?? 0) ?></div>
        <div class="mt-1 text-xs text-slate-500">Şirket / finans onayı bekleyen</div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between"><span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Onay Bekleyen Bayi</span><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-action"><i data-lucide="user-check" class="h-5 w-5"></i></span></div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= (int) ($kpi['pendingCompanies'] ?? 0) ?></div>
        <div class="mt-1 text-xs text-slate-500"><?= (int) ($kpi['activeCompanies'] ?? 0) ?> aktif bayi</div>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2 sm:p-5">
        <div class="mb-4 flex items-center justify-between">
            <div><h2 class="text-sm font-bold text-slate-800">6 Aylık B2B Ciro</h2><p class="text-xs text-slate-500">Tüm bayiler · KDV dahil</p></div>
        </div>
        <div class="h-56 w-full sm:h-64"><canvas id="adminRevenueChart"></canvas></div>
    </div>
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-sm font-bold text-slate-800">Riskli Bayiler</h2>
        <p class="text-xs text-slate-500">Limit aşımı veya vadesi geçmiş bakiyesi olanlar</p>
        <div class="mt-4 flex items-center gap-3">
            <span class="flex h-14 w-14 items-center justify-center rounded-2xl <?= ($kpi['riskCompanies'] ?? 0) > 0 ? 'bg-red-50 text-red-600' : 'bg-emerald-50 text-emerald-600' ?>"><i data-lucide="shield-alert" class="h-7 w-7"></i></span>
            <div><div class="font-mono text-3xl font-bold text-slate-900"><?= (int) ($kpi['riskCompanies'] ?? 0) ?></div><div class="text-xs text-slate-500">bayi risk takibinde</div></div>
        </div>
        <a href="<?= url('admin/finans') ?>" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Finans Raporuna Git</a>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
        <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Son Siparişler</h2><a href="<?= url('admin/siparisler') ?>" class="text-xs font-semibold text-action">Tümü →</a></div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-2 font-semibold">Sipariş</th><th class="px-3 py-2 font-semibold">Bayi</th><th class="px-3 py-2 font-semibold">Durum</th><th class="px-4 py-2 text-right font-semibold">Tutar</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentOrders as $o): ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-2.5"><a href="<?= url('admin/siparisler/' . $o['order_number']) ?>" class="font-mono text-xs font-semibold text-slate-800 hover:text-action"><?= e((string) $o['order_number']) ?></a><div class="text-[10px] text-slate-400"><?= e(tr_date((string) $o['created_at'])) ?></div></td>
                            <td class="px-3 py-2.5 text-xs text-slate-600"><?= e((string) $o['company_title']) ?></td>
                            <td class="px-3 py-2.5"><span class="inline-flex rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= e(order_status_classes((string) $o['status'])) ?>"><?= e(order_status_label((string) $o['status'])) ?></span></td>
                            <td class="px-4 py-2.5 text-right font-mono text-xs font-semibold text-slate-900"><?= e(money($o['grand_total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between"><h2 class="text-sm font-bold text-slate-800">Bayi Başvuruları</h2><a href="<?= url('admin/bayiler?durum=pending') ?>" class="text-xs font-semibold text-action">Tümü →</a></div>
            <?php if (empty($pendingList)): ?>
                <p class="mt-3 text-xs text-slate-500">Onay bekleyen başvuru yok.</p>
            <?php else: ?>
                <div class="mt-3 space-y-2">
                    <?php foreach ($pendingList as $p): ?>
                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 p-2.5">
                            <div class="min-w-0"><div class="truncate text-xs font-semibold text-slate-800"><?= e((string) $p['title']) ?></div><div class="text-[10px] text-slate-500"><?= e((string) $p['company_code']) ?> · <?= e((string) $p['city']) ?></div></div>
                            <a href="<?= url('admin/bayiler/' . $p['id']) ?>" class="shrink-0 rounded-lg bg-action px-2 py-1 text-[10px] font-semibold text-white">İncele</a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">En Yüksek Ciro</h2>
            <div class="mt-3 space-y-2">
                <?php foreach ($topCompanies as $tc): ?>
                    <div class="flex items-center justify-between gap-2 text-xs">
                        <span class="truncate text-slate-600"><?= e((string) $tc['title']) ?></span>
                        <span class="shrink-0 font-mono font-semibold text-slate-800"><?= e(money($tc['revenue'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var canvas = document.getElementById('adminRevenueChart');
        if (!canvas || typeof Chart === 'undefined') return;
        var ctx = canvas.getContext('2d', { willReadFrequently: true }) || canvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels ?? [], JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{ label: 'Ciro (₺)', data: <?= json_encode($chartValues ?? []) ?>, borderColor: '#2563EB', backgroundColor: 'rgba(37,99,235,0.12)', fill: true, tension: 0.35, pointRadius: 3, pointBackgroundColor: '#1E3A8A' }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(c.parsed.y); } } } },
                scales: { x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } }, y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { color: '#64748b', font: { size: 11 }, callback: function (v) { return new Intl.NumberFormat('tr-TR', { notation: 'compact' }).format(v) + ' ₺'; } } } }
            }
        });
    })();
</script>
