<?php
$firstName = explode(' ', (string) ($authUser['full_name'] ?? 'Bayi'))[0];
$usagePercent = (float) ($usagePercent ?? 0);
$available = (float) ($credit['available'] ?? 0);
$overdue = (float) ($credit['overdue'] ?? 0);
$usedLimit = (float) ($usedLimit ?? 0);
?>
<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Hoş geldiniz, <?= e($firstName) ?></h1>
        <p class="mt-1 text-sm text-slate-500"><?= e((string) ($company['title'] ?? '')) ?> · <?= e(tier_label((string) ($company['tier_level'] ?? 'bronze'))) ?> bayi hesabı</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('portal/hizli-siparis') ?>" class="inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white shadow-lg shadow-action/25 hover:bg-blue-700">
            <i data-lucide="plus" class="h-4 w-4"></i> Yeni Sipariş
        </a>
        <a href="<?= url('portal/katalog') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            <i data-lucide="layout-grid" class="h-4 w-4"></i> Katalog
        </a>
    </div>
</div>

<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Kullanılabilir Limit</span>
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600"><i data-lucide="shield-check" class="h-5 w-5"></i></span>
        </div>
        <div class="mt-3 font-mono text-2xl font-bold <?= $available < 0 ? 'text-red-600' : 'text-slate-900' ?>"><?= e(money($available)) ?></div>
        <div class="mt-1 text-xs text-slate-500">Toplam limit: <?= e(money($credit['limit'] ?? 0)) ?></div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Güncel Borç</span>
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-action"><i data-lucide="wallet" class="h-5 w-5"></i></span>
        </div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= e(money($credit['balance'] ?? 0)) ?></div>
        <div class="mt-1 text-xs <?= $overdue > 0 ? 'font-medium text-red-600' : 'text-slate-500' ?>">
            <?= $overdue > 0 ? 'Vadesi geçmiş: ' . e(money($overdue)) : 'Vadesi geçmiş borç yok' ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Onay Bekleyen</span>
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber"><i data-lucide="clock" class="h-5 w-5"></i></span>
        </div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= (int) ($pendingApprovals ?? 0) ?></div>
        <div class="mt-1 text-xs text-slate-500">Onay / finans bekleyen sipariş</div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <div class="flex items-center justify-between">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Aylık Alım</span>
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600"><i data-lucide="trending-up" class="h-5 w-5"></i></span>
        </div>
        <div class="mt-3 font-mono text-2xl font-bold text-slate-900"><?= e(money($monthlyPurchases ?? 0)) ?></div>
        <div class="mt-1 text-xs text-slate-500"><?= e(tr_date(date('Y-m-d'))) ?> itibarıyla · <?= (int) ($totalOrders ?? 0) ?> sipariş</div>
    </div>
</div>

<div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-3">
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2 sm:p-5">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-800">6 Aylık Alım Grafiği</h2>
                <p class="text-xs text-slate-500">KDV dahil sipariş tutarları</p>
            </div>
            <span class="rounded-lg bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">Son 6 Ay</span>
        </div>
        <div class="h-56 w-full sm:h-64">
            <canvas id="purchaseChart"></canvas>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
        <h2 class="text-sm font-bold text-slate-800">Risk & Limit Kullanımı</h2>
        <p class="text-xs text-slate-500">Açık hesap limiti durumu</p>
        <div class="mt-4">
            <div class="flex items-end justify-between">
                <span class="text-xs text-slate-500">Kullanılan</span>
                <span class="font-mono text-sm font-semibold text-slate-800"><?= e(money($usedLimit)) ?></span>
            </div>
            <div class="mt-1.5 h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full <?= $overdue > 0 ? 'bg-red-500' : ($usagePercent > 85 ? 'bg-amber' : 'bg-emerald-500') ?>" style="width: <?= e((string) min(100, max(0, $usagePercent))) ?>%"></div>
            </div>
            <div class="mt-1 flex justify-between text-[11px] text-slate-400"><span>0</span><span>%<?= e((string) $usagePercent) ?> kullanım</span></div>
        </div>
        <dl class="mt-4 space-y-2.5 text-sm">
            <div class="flex items-center justify-between"><dt class="text-slate-500">Tanımlı Limit</dt><dd class="font-mono font-semibold text-slate-800"><?= e(money($credit['limit'] ?? 0)) ?></dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">Cari Bakiye</dt><dd class="font-mono font-semibold text-slate-800"><?= e(money($credit['balance'] ?? 0)) ?></dd></div>
            <div class="flex items-center justify-between"><dt class="text-slate-500">Açık Siparişler</dt><dd class="font-mono font-semibold text-slate-800"><?= e(money($credit['open_orders'] ?? 0)) ?></dd></div>
            <div class="flex items-center justify-between border-t border-slate-100 pt-2.5"><dt class="font-medium text-slate-600">Kullanılabilir</dt><dd class="font-mono font-bold <?= $available < 0 ? 'text-red-600' : 'text-emerald-600' ?>"><?= e(money($available)) ?></dd></div>
        </dl>
        <div class="mt-4 rounded-xl <?= $overdue > 0 ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700' ?> px-3 py-2 text-xs font-medium">
            <?= $overdue > 0 ? 'Dikkat: Vadesi geçmiş bakiyeniz bulunmaktadır. Vadeli siparişler finans onayına düşecektir.' : 'Hesabınız açık hesap siparişlerine uygundur.' ?>
        </div>
    </div>
</div>

<div class="mt-4 rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3.5 sm:px-5">
        <h2 class="text-sm font-bold text-slate-800">Son Siparişler</h2>
        <a href="<?= url('portal/siparisler') ?>" class="text-xs font-semibold text-action hover:text-blue-700">Tümünü Gör →</a>
    </div>
    <?php if (empty($recentOrders)): ?>
        <div class="px-5 py-10 text-center text-sm text-slate-500">Henüz siparişiniz bulunmuyor.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-2.5 font-semibold sm:px-5">Sipariş No</th>
                        <th class="px-4 py-2.5 font-semibold">Tarih</th>
                        <th class="px-4 py-2.5 font-semibold">Kalem</th>
                        <th class="px-4 py-2.5 font-semibold">Durum</th>
                        <th class="px-4 py-2.5 text-right font-semibold sm:px-5">Tutar</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentOrders as $order): ?>
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-800 sm:px-5"><?= e((string) $order['order_number']) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= e(tr_date((string) $order['created_at'])) ?></td>
                            <td class="px-4 py-3 text-slate-600"><?= (int) $order['item_count'] ?> kalem</td>
                            <td class="px-4 py-3"><span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-semibold <?= e(order_status_classes((string) $order['status'])) ?>"><?= e(order_status_label((string) $order['status'])) ?></span></td>
                            <td class="px-4 py-3 text-right font-mono font-semibold text-slate-800 sm:px-5"><?= e(money($order['grand_total'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="mt-6">
    <div class="mb-3 flex items-center justify-between">
        <h2 class="text-sm font-bold text-slate-800">Öne Çıkan Ürünler</h2>
        <a href="<?= url('portal/katalog') ?>" class="text-xs font-semibold text-action hover:text-blue-700">Kataloğa Git →</a>
    </div>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <?php foreach (($featured ?? []) as $p): ?>
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="aspect-[4/3] w-full overflow-hidden bg-slate-100">
                    <img src="<?= asset((string) $p['main_image']) ?>" alt="<?= e((string) $p['name']) ?>" loading="lazy" class="h-full w-full object-cover">
                </div>
                <div class="p-3.5">
                    <div class="font-mono text-[11px] font-medium text-slate-500"><?= e((string) $p['sku']) ?></div>
                    <div class="mt-1 line-clamp-2 min-h-[2.5rem] text-sm font-semibold leading-snug text-slate-800"><?= e((string) $p['name']) ?></div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="font-mono text-sm font-bold text-slate-900"><?= e(money($p['base_list_price'], $p['currency'] === 'EUR' ? '€' : ($p['currency'] === 'USD' ? '$' : '₺'))) ?></span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?= (int) $p['stock'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $p['stock'] > 0 ? 'Stokta' : 'Stok Yok' ?></span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
    (function () {
        var canvas = document.getElementById('purchaseChart');
        if (!canvas || typeof Chart === 'undefined') return;
        var ctx = canvas.getContext('2d', { willReadFrequently: true }) || canvas.getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels ?? [], JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Alım Tutarı (₺)',
                    data: <?= json_encode($chartValues ?? []) ?>,
                    backgroundColor: 'rgba(37, 99, 235, 0.85)',
                    hoverBackgroundColor: '#1E3A8A',
                    borderRadius: 8,
                    maxBarThickness: 44
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (c) {
                                return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(c.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 11 } } },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e2e8f0' },
                        ticks: {
                            color: '#64748b', font: { size: 11 },
                            callback: function (v) { return new Intl.NumberFormat('tr-TR', { notation: 'compact' }).format(v) + ' ₺'; }
                        }
                    }
                }
            }
        });
    })();
</script>
