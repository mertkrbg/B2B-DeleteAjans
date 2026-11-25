<?php
/** @var string $content */
$flashes = \App\Core\Session::getFlashes();
$navItems = [
    ['key' => '', 'label' => 'Kokpit', 'icon' => 'layout-dashboard', 'href' => 'portal'],
    ['key' => 'katalog', 'label' => 'Ürün Kataloğu', 'icon' => 'layout-grid', 'href' => 'portal/katalog'],
    ['key' => 'hizli-siparis', 'label' => 'Hızlı Sipariş', 'icon' => 'list-plus', 'href' => 'portal/hizli-siparis'],
    ['key' => 'teklifler', 'label' => 'Tekliflerim (RFQ)', 'icon' => 'file-signature', 'href' => 'portal/teklifler'],
    ['key' => 'siparisler', 'label' => 'Siparişlerim', 'icon' => 'package', 'href' => 'portal/siparisler'],
    ['key' => 'cari-hesap', 'label' => 'Cari Hesap', 'icon' => 'wallet', 'href' => 'portal/cari-hesap'],
    ['key' => 'ekip', 'label' => 'Firma & Ekip', 'icon' => 'users', 'href' => 'portal/ekip'],
];
$isActive = static function (string $key, string $currentPath): bool {
    if ($key === '') {
        return $currentPath === '/portal' || $currentPath === '/portal/';
    }
    return str_starts_with($currentPath, '/portal/' . $key);
};
$role = (string) ($authUser['role'] ?? '');
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($pageTitle ?? 'Portal') . ' · ' . config('app.name')) ?></title>
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
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 no-scrollbar">
            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-widest text-slate-500">Bayi Portalı</p>
            <?php foreach ($navItems as $item): ?>
                <?php $active = $isActive($item['key'], $currentPath); ?>
                <a href="<?= url($item['href']) ?>"
                   class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition <?= $active ? 'bg-action text-white shadow-lg shadow-action/20' : 'text-slate-300 hover:bg-white/5 hover:text-white' ?>">
                    <i data-lucide="<?= e($item['icon']) ?>" class="h-[18px] w-[18px]"></i>
                    <span><?= e($item['label']) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <div class="border-t border-white/10 p-4">
            <div class="rounded-xl bg-white/5 p-3">
                <div class="flex items-center justify-between text-[11px] text-slate-400">
                    <span>Kullanılabilir Limit</span>
                    <span class="rounded-full bg-emerald-500/15 px-2 py-0.5 font-semibold text-emerald-300"><?= e(tier_label((string) ($company['tier_level'] ?? 'bronze'))) ?></span>
                </div>
                <div class="mt-1 font-mono text-lg font-semibold <?= ($credit['available'] ?? 0) < 0 ? 'text-red-300' : 'text-white' ?>">
                    <?= e(money($credit['available'] ?? 0)) ?>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-white/10">
                    <div class="h-full rounded-full <?= ($credit['has_overdue'] ?? false) ? 'bg-red-400' : 'bg-emerald-400' ?>" style="width: <?= e((string) min(100, max(0, (int) round((($credit['limit'] ?? 0) > 0 ? (($credit['balance'] ?? 0) + ($credit['open_orders'] ?? 0)) / $credit['limit'] * 100 : 0))))) ?>%"></div>
                </div>
            </div>
            <p class="mt-3 px-1 text-[11px] text-slate-500"><?= e((string) ($company['title'] ?? '')) ?></p>
        </div>
    </aside>

    <div class="lg:pl-64">
        <header class="sticky top-0 z-20 flex h-16 items-center gap-2 border-b border-slate-200 bg-white/95 px-3 backdrop-blur sm:gap-3 sm:px-5">
            <button @click="sidebar=true" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Menüyü aç">
                <i data-lucide="menu" class="h-5 w-5"></i>
            </button>

            <form action="<?= url('portal/katalog') ?>" method="GET" class="relative hidden flex-1 max-w-md sm:block">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </span>
                <input id="globalSearch" type="search" name="q" value="<?= e((string) ($_GET['q'] ?? '')) ?>"
                       placeholder="SKU, barkod, OEM kodu veya ürün adı ara (Ctrl+K)"
                       class="w-full rounded-xl border border-slate-200 bg-slate-50 py-2 pl-9 pr-3 text-sm text-slate-700 placeholder-slate-400 focus:border-action focus:bg-white focus:ring-2 focus:ring-action/20">
            </form>

            <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
                <div class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 md:flex">
                    <i data-lucide="shield-check" class="h-4 w-4 text-emerald-600"></i>
                    <div class="leading-tight">
                        <div class="font-mono text-sm font-semibold text-slate-800"><?= e(money($credit['available'] ?? 0)) ?></div>
                        <div class="text-[10px] text-slate-500">Limit · <?= e((string) ($credit['term_days'] ?? 0)) ?> gün vade</div>
                    </div>
                </div>

                <a href="<?= url('portal/sepet') ?>" class="relative flex items-center gap-2 rounded-xl border border-slate-200 px-2.5 py-1.5 text-slate-700 hover:bg-slate-50">
                    <i data-lucide="shopping-cart" class="h-5 w-5"></i>
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block font-mono text-xs font-semibold"><?= e(money($cartSummary['gross'] ?? 0)) ?></span>
                        <span class="block text-[10px] text-slate-500"><?= (int) ($cartSummary['count'] ?? 0) ?> kalem</span>
                    </span>
                    <?php if (($cartSummary['count'] ?? 0) > 0): ?>
                        <span class="flex h-4 min-w-4 items-center justify-center rounded-full bg-amber px-1 text-[10px] font-bold text-white"><?= (int) $cartSummary['count'] ?></span>
                    <?php endif; ?>
                </a>

                <div class="relative" @click.outside="profile=false">
                    <button @click="profile = !profile" class="flex items-center gap-2 rounded-xl border border-slate-200 px-2 py-1.5 hover:bg-slate-50">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-corporate text-sm font-bold text-white">
                            <?= e(mb_substr((string) ($authUser['full_name'] ?? 'B'), 0, 1)) ?>
                        </span>
                        <span class="hidden text-left leading-tight lg:block">
                            <span class="block max-w-[140px] truncate text-xs font-semibold text-slate-800"><?= e((string) ($authUser['full_name'] ?? '')) ?></span>
                            <span class="block text-[10px] text-slate-500"><?= e(role_label($role)) ?></span>
                        </span>
                        <i data-lucide="chevron-down" class="hidden h-4 w-4 text-slate-400 lg:block"></i>
                    </button>
                    <div x-show="profile" x-cloak class="absolute right-0 mt-2 w-64 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl">
                        <div class="border-b border-slate-100 px-4 py-3">
                            <p class="text-sm font-semibold text-slate-800"><?= e((string) ($authUser['full_name'] ?? '')) ?></p>
                            <p class="text-xs text-slate-500"><?= e((string) ($authUser['email'] ?? '')) ?></p>
                            <p class="mt-1 text-[11px] font-medium text-corporate"><?= e((string) ($company['title'] ?? '')) ?></p>
                        </div>
                        <a href="<?= url('portal/ekip') ?>" class="flex items-center gap-2 px-4 py-2.5 text-sm text-slate-600 hover:bg-slate-50"><i data-lucide="user-cog" class="h-4 w-4"></i> Hesap & Ekip</a>
                        <a href="<?= url('logout') ?>" class="flex items-center gap-2 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50"><i data-lucide="log-out" class="h-4 w-4"></i> Güvenli Çıkış</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-[1600px] px-3 py-5 pb-24 sm:px-5 lg:px-8 lg:py-6 lg:pb-8">
            <?php foreach ($flashes as $type => $messages): ?>
                <?php
                $styles = [
                    'success' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                    'error'   => 'bg-red-50 border-red-200 text-red-800',
                    'warning' => 'bg-amber-50 border-amber-200 text-amber-800',
                    'info'    => 'bg-blue-50 border-blue-200 text-blue-800',
                ];
                $icons = ['success' => 'check-circle', 'error' => 'alert-triangle', 'warning' => 'alert-circle', 'info' => 'info'];
                ?>
                <?php foreach ($messages as $message): ?>
                    <div class="mb-3 flex items-start gap-3 rounded-xl border px-4 py-3 text-sm <?= e($styles[$type] ?? $styles['info']) ?>" role="alert">
                        <i data-lucide="<?= e($icons[$type] ?? 'info') ?>" class="mt-0.5 h-4 w-4 shrink-0"></i>
                        <span class="leading-relaxed"><?= e($message) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?= $content ?>
        </main>
    </div>

    <nav class="fixed inset-x-0 bottom-0 z-20 grid grid-cols-4 border-t border-slate-200 bg-white/95 backdrop-blur lg:hidden">
        <a href="<?= url('portal/katalog') ?>" class="flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium text-slate-600"><i data-lucide="layout-grid" class="h-5 w-5"></i>Katalog</a>
        <a href="<?= url('portal/hizli-siparis') ?>" class="flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium text-slate-600"><i data-lucide="list-plus" class="h-5 w-5"></i>Hızlı Sipariş</a>
        <a href="<?= url('portal/sepet') ?>" class="relative flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium text-slate-600"><i data-lucide="shopping-cart" class="h-5 w-5"></i>Sepet<?php if (($cartSummary['count'] ?? 0) > 0): ?><span class="absolute right-4 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-amber px-1 text-[9px] font-bold text-white"><?= (int) $cartSummary['count'] ?></span><?php endif; ?></a>
        <a href="<?= url('portal/cari-hesap') ?>" class="flex flex-col items-center gap-0.5 py-2.5 text-[10px] font-medium text-slate-600"><i data-lucide="wallet" class="h-5 w-5"></i>Cari</a>
    </nav>
</div>

<script src="https://unpkg.com/lucide@latest"></script>
<script src="<?= asset('js/b2b-app.js') ?>"></script>
<script>
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            var el = document.getElementById('globalSearch');
            if (el) { e.preventDefault(); el.focus(); }
        }
    });
</script>
</body>
</html>
