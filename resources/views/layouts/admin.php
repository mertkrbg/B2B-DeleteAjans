<?php
/** @var string $content */
$flashes = \App\Core\Session::getFlashes();
$navItems = [
    ['key' => '', 'label' => 'Kokpit', 'icon' => 'layout-dashboard', 'href' => 'admin', 'badge' => 0],
    ['key' => 'bayiler', 'label' => 'Bayiler', 'icon' => 'building-2', 'href' => 'admin/bayiler', 'badge' => $counts['pendingCompanies'] ?? 0],
    ['key' => 'urunler', 'label' => 'Ürünler & Stok', 'icon' => 'package', 'href' => 'admin/urunler', 'badge' => 0],
    ['key' => 'fiyat-listeleri', 'label' => 'Fiyat Listeleri', 'icon' => 'tags', 'href' => 'admin/fiyat-listeleri', 'badge' => 0],
    ['key' => 'siparisler', 'label' => 'Siparişler', 'icon' => 'clipboard-list', 'href' => 'admin/siparisler', 'badge' => $counts['pendingOrders'] ?? 0],
    ['key' => 'teklifler', 'label' => 'Teklifler (RFQ)', 'icon' => 'file-signature', 'href' => 'admin/teklifler', 'badge' => $counts['pendingQuotes'] ?? 0],
    ['key' => 'finans', 'label' => 'Finans', 'icon' => 'wallet', 'href' => 'admin/finans', 'badge' => $counts['pendingPayments'] ?? 0],
    ['key' => 'ayarlar', 'label' => 'Ayarlar & Log', 'icon' => 'settings', 'href' => 'admin/ayarlar', 'badge' => 0],
];
$isActive = static function (string $key, string $currentPath): bool {
    if ($key === '') {
        return $currentPath === '/admin' || $currentPath === '/admin/';
    }
    return str_starts_with($currentPath, '/admin/' . $key);
};
$role = (string) ($authUser['role'] ?? '');
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($pageTitle ?? 'Yönetim') . ' · ' . config('app.name')) ?> (Admin)</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { navy:'#0F172A', corporate:{DEFAULT:'#1E3A8A',light:'#1D4ED8'}, action:'#2563EB', canvas:'#F8FAFC',
                amber:{ DEFAULT:'#D97706', 50:'#FFFBEB', 100:'#FEF3C7', 200:'#FDE68A', 300:'#FCD34D', 400:'#FBBF24', 500:'#F59E0B', 600:'#D97706', 700:'#B45309', 800:'#92400E', 900:'#78350F' } },
            fontFamily: { sans:['Inter','system-ui','Segoe UI','sans-serif'], mono:['JetBrains Mono','ui-monospace','monospace'] }
        } } }
    </script>
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
    <script defer src="https://unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-canvas text-slate-900 antialiased overflow-x-hidden" x-data="{ sidebar:false, profile:false }">
<div class="min-h-full">
    <div x-show="sidebar" x-cloak @click="sidebar=false" class="fixed inset-0 z-30 bg-slate-900/50 lg:hidden"></div>

    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-navy text-slate-300 transition-transform duration-200 -translate-x-full lg:translate-x-0"
           :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'">
        <div class="flex h-16 shrink-0 items-center gap-2 border-b border-white/10 px-5">
            <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise" width="200" height="44" class="h-9 w-auto brightness-0 invert">
            <span class="rounded-md bg-amber px-1.5 py-0.5 text-[9px] font-bold uppercase text-white">ERP</span>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 no-scrollbar">
            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-500">Yönetim Paneli</p>
            <?php foreach ($navItems as $item): ?>
                <?php $active = $isActive($item['key'], $currentPath); ?>
                <a href="<?= url($item['href']) ?>" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition <?= $active ? 'bg-action text-white shadow-lg shadow-action/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="<?= e($item['icon']) ?>" class="h-[18px] w-[18px]"></i>
                    <span class="flex-1"><?= e($item['label']) ?></span>
                    <?php if (($item['badge'] ?? 0) > 0): ?><span class="flex h-5 min-w-5 items-center justify-center rounded-full bg-amber px-1.5 text-[10px] font-bold text-white"><?= (int) $item['badge'] ?></span><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="border-t border-white/10 p-4">
            <p class="text-[10px] text-slate-500">B2B Enterprise ERP</p>
            <p class="text-[11px] text-slate-400">v1.0 · Yönetim Konsolu</p>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white/95 px-3 backdrop-blur sm:px-5">
            <button @click="sidebar=true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menüyü aç"><i data-lucide="menu" class="h-5 w-5"></i></button>
            <div class="min-w-0">
                <h1 class="truncate text-sm font-bold text-slate-800 sm:text-base"><?= e((string) ($pageTitle ?? 'Yönetim')) ?></h1>
            </div>
            <div class="ml-auto flex items-center gap-2">
                <a href="<?= url('portal') ?>" class="hidden rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 sm:inline-flex">Bayi Portalı</a>
                <div class="relative" @click.outside="profile=false">
                    <button @click="profile = !profile" class="flex items-center gap-2 rounded-xl border border-slate-200 px-2 py-1.5 hover:bg-slate-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-navy text-sm font-bold text-white"><?= e(mb_substr((string) ($authUser['full_name'] ?? 'A'), 0, 1)) ?></span>
                        <span class="hidden text-left leading-tight sm:block">
                            <span class="block max-w-[140px] truncate text-xs font-semibold text-slate-800"><?= e((string) ($authUser['full_name'] ?? '')) ?></span>
                            <span class="block text-[10px] text-slate-500"><?= e(role_label($role)) ?></span>
                        </span>
                        <i data-lucide="chevron-down" class="hidden h-4 w-4 text-slate-400 sm:block"></i>
                    </button>
                    <div x-show="profile" x-cloak class="absolute right-0 mt-2 w-60 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-800"><?= e((string) ($authUser['full_name'] ?? '')) ?></p>
                            <p class="text-xs text-slate-500"><?= e((string) ($authUser['email'] ?? '')) ?></p>
                        </div>
                        <a href="<?= url('logout') ?>" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50"><i data-lucide="log-out" class="h-4 w-4"></i> Güvenli Çıkış</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1700px] px-3 py-5 sm:px-5 lg:px-8 lg:py-6">
            <?php foreach ($flashes as $type => $messages): ?>
                <?php
                $styles = ['success' => 'bg-emerald-50 border-emerald-200 text-emerald-800', 'error' => 'bg-red-50 border-red-200 text-red-800', 'warning' => 'bg-amber-50 border-amber-200 text-amber-800', 'info' => 'bg-blue-50 border-blue-200 text-blue-800'];
                $icons = ['success' => 'check-circle', 'error' => 'alert-triangle', 'warning' => 'alert-circle', 'info' => 'info'];
                ?>
                <?php foreach ($messages as $message): ?>
                    <div class="mb-3 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm <?= e($styles[$type] ?? $styles['info']) ?>">
                        <i data-lucide="<?= e($icons[$type] ?? 'info') ?>" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <span class="leading-relaxed"><?= e($message) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= asset('js/b2b-app.js') ?>"></script>
</body>
</html>
