<?php
/** @var string $content */
$flashes = \App\Core\Session::getFlashes();
$appName = (string) config('app.name', 'B2B Enterprise Portal');
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(($title ?? 'Giriş') . ' · ' . $appName) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        navy: '#0F172A',
                        corporate: { DEFAULT: '#1E3A8A', light: '#1D4ED8' },
                        action: '#2563EB',
                        amber: { DEFAULT: '#D97706', 50: '#FFFBEB', 100: '#FEF3C7', 200: '#FDE68A', 300: '#FCD34D', 400: '#FBBF24', 500: '#F59E0B', 600: '#D97706', 700: '#B45309', 800: '#92400E', 900: '#78350F' },
                        canvas: '#F8FAFC'
                    },
                    fontFamily: {
                        sans: ['Inter', 'system-ui', 'Segoe UI', 'sans-serif'],
                        mono: ['JetBrains Mono', 'ui-monospace', 'monospace']
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="<?= asset('css/custom.css') ?>">
    <script defer src="https://unpkg.com/alpinejs@3.13.10/dist/cdn.min.js"></script>
</head>
<body class="h-full bg-canvas antialiased">
    <div class="min-h-full w-full flex flex-col lg:flex-row">

        <!-- Brand / marketing panel -->
        <aside class="brand-gradient relative overflow-hidden text-white lg:w-[46%] lg:min-h-screen">
            <div class="absolute inset-0 brand-grid-pattern opacity-60"></div>
            <div class="relative z-10 flex h-full flex-col justify-between px-6 py-8 sm:px-10 lg:px-14 lg:py-12">
                <div class="flex items-center gap-3">
                    <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise Portal" width="220" height="48" class="h-10 w-auto brightness-0 invert">
                </div>

                <div class="mt-10 hidden max-w-md lg:mt-0 lg:block">
                    <h1 class="text-3xl font-extrabold leading-tight tracking-tight xl:text-4xl">
                        Kurumsal Tedarik Zincirinizi<br><span class="text-blue-300">Tek Panelde</span> Yönetin
                    </h1>
                    <p class="mt-4 text-sm leading-relaxed text-slate-300">
                        Çok katmanlı bayi hiyerarşisi, cari risk kontrolü, kişiye özel fiyat listeleri ve
                        kademeli iskontolarla B2B satın alma süreçlerinizi hızlandırın.
                    </p>
                    <ul class="mt-8 space-y-3 text-sm text-slate-200">
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <i data-lucide="shield-check" class="h-4 w-4"></i>
                            </span>
                            <span>Risk limiti ve vadesi geçmiş bakiye anlık kontrolü</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <i data-lucide="layers" class="h-4 w-4"></i>
                            </span>
                            <span>Çoklu depo stok görünürlüğü ve kademeli iskontolar</span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-white/10">
                                <i data-lucide="file-text" class="h-4 w-4"></i>
                            </span>
                            <span>Tek tıkla teklif (RFQ), sipariş ve proforma belgeleri</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-8 hidden gap-6 border-t border-white/15 pt-6 lg:grid lg:grid-cols-3 lg:mt-0">
                    <div>
                        <div class="text-2xl font-bold tabular-nums">24+</div>
                        <div class="text-xs text-slate-400">Endüstriyel Ürün</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold tabular-nums">3</div>
                        <div class="text-xs text-slate-400">Aktif Depo Lokasyonu</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold tabular-nums">%12</div>
                        <div class="text-xs text-slate-400">Gold Bayi İskontosu</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Form panel -->
        <main class="flex flex-1 items-center justify-center px-4 py-8 sm:px-8 lg:px-12 lg:py-12">
            <div class="w-full max-w-md">
                <?php foreach ($flashes as $type => $messages): ?>
                    <?php
                    $styles = [
                        'success' => ['bg-emerald-50', 'border-emerald-200', 'text-emerald-800', 'check-circle'],
                        'error'   => ['bg-red-50', 'border-red-200', 'text-red-800', 'alert-triangle'],
                        'warning' => ['bg-amber-50', 'border-amber-200', 'text-amber-800', 'alert-circle'],
                        'info'    => ['bg-blue-50', 'border-blue-200', 'text-blue-800', 'info'],
                    ];
                    $s = $styles[$type] ?? $styles['info'];
                    ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="mb-4 flex items-start gap-3 rounded-xl border <?= e($s[1]) ?> <?= e($s[0]) ?> px-4 py-3 text-sm <?= e($s[2]) ?>" role="alert">
                            <i data-lucide="<?= e($s[3]) ?>" class="mt-0.5 h-5 w-5 shrink-0"></i>
                            <span class="leading-relaxed"><?= e($message) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>

                <?= $content ?>
            </div>
        </main>
    </div>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="<?= asset('js/b2b-app.js') ?>"></script>
</body>
</html>
