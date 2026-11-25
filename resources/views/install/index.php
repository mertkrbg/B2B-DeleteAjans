<?php
$requirements = $requirements ?? [];
$canInstall = $canInstall ?? false;
$errors = $errors ?? [];
$old = $old ?? [];
$detectedUrl = $detectedUrl ?? '';
$val = static fn (string $k, string $d = ''): string => (string) ($old[$k] ?? $d);
$err = static fn (string $k): ?string => $errors[$k] ?? null;
$requiredMissing = 0;
foreach ($requirements as $r) {
    if (!empty($r['required']) && empty($r['ok'])) { $requiredMissing++; }
}
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Kurulum') . ' — ' . 'B2B Enterprise Portal') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { navy:'#0F172A', corporate:{DEFAULT:'#1E3A8A',light:'#1D4ED8'}, action:'#2563EB', canvas:'#F8FAFC',
                amber:{ DEFAULT:'#D97706', 50:'#FFFBEB', 100:'#FEF3C7', 200:'#FDE68A', 600:'#D97706', 700:'#B45309', 800:'#92400E' } },
            fontFamily: { sans:['Inter','system-ui','Segoe UI','sans-serif'], mono:['JetBrains Mono','ui-monospace','monospace'] }
        } } }
    </script>
</head>
<body class="min-h-full bg-canvas text-slate-900 antialiased">
<div class="mx-auto flex min-h-screen max-w-[1280px] flex-col lg:flex-row">

    <aside class="relative flex flex-col justify-between bg-navy p-8 text-slate-300 lg:w-[400px] lg:shrink-0 lg:rounded-br-[40px] lg:p-10">
        <div>
            <div class="flex items-center gap-2">
                <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise" width="200" height="44" class="h-10 w-auto brightness-0 invert">
                <span class="rounded-md bg-amber px-1.5 py-0.5 text-[9px] font-bold uppercase text-white">Kurulum</span>
            </div>
            <h1 class="mt-10 text-2xl font-bold leading-tight text-white">B2B Enterprise Portal<br>Kurulum Sihirbazı</h1>
            <p class="mt-3 text-sm leading-relaxed text-slate-400">Kurumsal bayi yönetim sisteminizi birkaç adımda, güvenli ve eksiksiz biçimde yapılandırın.</p>
            <ul class="mt-8 space-y-3 text-sm">
                <?php foreach ([
                    ['Veritabanı şeması otomatik oluşturulur', 'database'],
                    ['Yönetici hesabı güvenli şifreleme ile kaydedilir', 'shield-check'],
                    ['Kurulum kilidi ile tekrar kurulum engellenir', 'lock'],
                    ['Gerçekçi örnek veri isteğe bağlı yüklenir', 'sparkles'],
                ] as [$text, $icon]): ?>
                    <li class="flex items-start gap-3"><i data-lucide="<?= e($icon) ?>" class="mt-0.5 h-4 w-4 shrink-0 text-sky-400"></i><span class="text-slate-300"><?= e($text) ?></span></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <p class="mt-10 text-[11px] text-slate-500">Sürüm <?= e(\App\Core\Installer::VERSION) ?> · PHP <?= e(PHP_VERSION) ?></p>
    </aside>

    <main class="flex-1 px-5 py-8 sm:px-8 lg:px-12 lg:py-12">
        <?php if (!empty($errors['genel'])): ?>
            <div class="mb-5 flex items-start gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <i data-lucide="alert-triangle" class="mt-0.5 h-4 w-4 shrink-0"></i><span class="leading-relaxed"><?= e((string) $errors['genel']) ?></span>
            </div>
        <?php endif; ?>

        <section class="mb-6">
            <div class="flex items-center justify-between">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="server-cog" class="h-4 w-4 text-action"></i> Sunucu Gereksinimleri</h2>
                <?php if ($requiredMissing === 0): ?>
                    <span class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700"><i data-lucide="check-circle" class="h-3.5 w-3.5"></i> Tümü hazır</span>
                <?php else: ?>
                    <span class="inline-flex items-center gap-1 rounded-full border border-red-200 bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700"><i data-lucide="x-circle" class="h-3.5 w-3.5"></i> <?= (int) $requiredMissing ?> eksik</span>
                <?php endif; ?>
            </div>
            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2">
                <?php foreach ($requirements as $r): ?>
                    <div class="flex items-center justify-between gap-2 rounded-lg border px-3 py-2 text-xs <?= !empty($r['ok']) ? 'border-slate-200 bg-white' : (!empty($r['required']) ? 'border-red-200 bg-red-50' : 'border-amber-200 bg-amber-50') ?>">
                        <span class="flex items-center gap-2 text-slate-700">
                            <?php if (!empty($r['ok'])): ?><i data-lucide="check-circle" class="h-3.5 w-3.5 text-emerald-600"></i>
                            <?php elseif (!empty($r['required'])): ?><i data-lucide="x-circle" class="h-3.5 w-3.5 text-red-600"></i>
                            <?php else: ?><i data-lucide="alert-circle" class="h-3.5 w-3.5 text-amber-600"></i><?php endif; ?>
                            <?= e((string) $r['label']) ?>
                        </span>
                        <span class="font-mono text-[10px] text-slate-500"><?= e((string) $r['value']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <form action="<?= url('kurulum') ?>" method="POST" autocomplete="off" class="space-y-6">
            <?= csrf_field() ?>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="database" class="h-4 w-4 text-action"></i> Veritabanı Bilgileri</h2>
                <p class="mt-1 text-xs text-slate-500">MySQL / MariaDB sunucu bilgilerini girin. Veritabanı yoksa otomatik oluşturulur.</p>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="db_host" class="mb-1 block text-xs font-semibold text-slate-600">Sunucu</label>
                        <input type="text" id="db_host" name="db_host" value="<?= e($val('db_host', '127.0.0.1')) ?>" required class="w-full rounded-lg border <?= $err('db_host') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <?php if ($err('db_host')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('db_host')) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="db_port" class="mb-1 block text-xs font-semibold text-slate-600">Port</label>
                        <input type="number" id="db_port" name="db_port" value="<?= e($val('db_port', '3306')) ?>" required class="w-full rounded-lg border <?= $err('db_port') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm font-mono">
                        <?php if ($err('db_port')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('db_port')) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="db_name" class="mb-1 block text-xs font-semibold text-slate-600">Veritabanı Adı</label>
                        <input type="text" id="db_name" name="db_name" value="<?= e($val('db_name', 'b2b_enterprise')) ?>" required class="w-full rounded-lg border <?= $err('db_name') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm font-mono">
                        <?php if ($err('db_name')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('db_name')) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="db_user" class="mb-1 block text-xs font-semibold text-slate-600">Kullanıcı Adı</label>
                        <input type="text" id="db_user" name="db_user" value="<?= e($val('db_user', 'root')) ?>" required class="w-full rounded-lg border <?= $err('db_user') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <?php if ($err('db_user')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('db_user')) ?></p><?php endif; ?>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="db_pass" class="mb-1 block text-xs font-semibold text-slate-600">Şifre <span class="font-normal text-slate-400">(boşsa boş bırakın)</span></label>
                        <input type="password" id="db_pass" name="db_pass" value="<?= e($val('db_pass')) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="globe" class="h-4 w-4 text-action"></i> Uygulama Adresi</h2>
                <p class="mt-1 text-xs text-slate-500">Site adresi (DocumentRoot <code class="rounded bg-slate-100 px-1">public/</code> olmalıdır). Otomatik algılandı, gerekirse düzenleyin.</p>
                <div class="mt-4">
                    <input type="url" id="app_url" name="app_url" value="<?= e($val('app_url', $detectedUrl)) ?>" required class="w-full rounded-lg border <?= $err('app_url') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm font-mono">
                    <?php if ($err('app_url')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('app_url')) ?></p><?php endif; ?>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="user-shield" class="h-4 w-4 text-action"></i> Yönetici Hesabı</h2>
                <p class="mt-1 text-xs text-slate-500">Sistem yöneticisi (super_admin) hesabınızı oluşturun.</p>
                <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label for="admin_name" class="mb-1 block text-xs font-semibold text-slate-600">Ad Soyad</label>
                        <input type="text" id="admin_name" name="admin_name" value="<?= e($val('admin_name')) ?>" required class="w-full rounded-lg border <?= $err('admin_name') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <?php if ($err('admin_name')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('admin_name')) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="admin_email" class="mb-1 block text-xs font-semibold text-slate-600">E-posta</label>
                        <input type="email" id="admin_email" name="admin_email" value="<?= e($val('admin_email')) ?>" required class="w-full rounded-lg border <?= $err('admin_email') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <?php if ($err('admin_email')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('admin_email')) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="admin_password" class="mb-1 block text-xs font-semibold text-slate-600">Şifre</label>
                        <input type="password" id="admin_password" name="admin_password" required class="w-full rounded-lg border <?= $err('admin_password') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <p class="mt-1 text-[10px] text-slate-400">En az 8 karakter, bir harf ve bir rakam.</p>
                        <?php if ($err('admin_password')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('admin_password')) ?></p><?php endif; ?>
                    </div>
                    <div>
                        <label for="admin_password_confirm" class="mb-1 block text-xs font-semibold text-slate-600">Şifre (Tekrar)</label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm" required class="w-full rounded-lg border <?= $err('admin_password_confirm') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <?php if ($err('admin_password_confirm')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('admin_password_confirm')) ?></p><?php endif; ?>
                    </div>
                </div>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="building-2" class="h-4 w-4 text-action"></i> Firma & Seçenekler</h2>
                <div class="mt-4 space-y-3">
                    <div>
                        <label for="company_title" class="mb-1 block text-xs font-semibold text-slate-600">Firma Unvanı</label>
                        <input type="text" id="company_title" name="company_title" value="<?= e($val('company_title', 'B2B Enterprise Tedarik A.Ş.')) ?>" required class="w-full rounded-lg border <?= $err('company_title') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm">
                        <?php if ($err('company_title')): ?><p class="mt-1 text-[11px] text-red-600"><?= e($err('company_title')) ?></p><?php endif; ?>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50/60 p-3">
                        <input type="checkbox" name="demo_data" value="1" <?= ($val('demo_data', '1') === '1') ? 'checked' : '' ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-action focus:ring-action">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Örnek veri yükle</span>
                            <span class="block text-xs text-slate-500">24 ürün, 5 bayi, örnek siparişler, cari hareketler ve test hesapları ile dolu bir demo ortamı oluşturur. (Geliştirme/demo için önerilir.)</span>
                        </span>
                    </label>
                </div>
            </section>

            <div class="flex flex-col-reverse items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-500">Kurulum tamamlandığında <code class="rounded bg-slate-100 px-1 font-mono">storage/installed.lock</code> oluşturulur ve tekrar kurulum engellenir.</p>
                <button type="submit" <?= $canInstall ? '' : 'disabled' ?> class="inline-flex items-center justify-center gap-2 rounded-xl bg-action px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-action/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300 disabled:shadow-none">
                    <i data-lucide="rocket" class="h-4 w-4"></i> Kurulumu Başlat
                </button>
            </div>
        </form>
    </main>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) { lucide.createIcons(); }</script>
</body>
</html>
