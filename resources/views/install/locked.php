<?php
$state = $state ?? [];
$installedAt = (string) ($state['installed_at'] ?? '');
$adminEmail = (string) ($state['admin_email'] ?? '');
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kurulum Zaten Tamamlandı</title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { navy:'#0F172A', action:'#2563EB', canvas:'#F8FAFC' },
            fontFamily: { sans:['Inter','system-ui','Segoe UI','sans-serif'], mono:['JetBrains Mono','ui-monospace','monospace'] }
        } } }
    </script>
</head>
<body class="flex min-h-screen items-center justify-center bg-canvas p-5 text-slate-900 antialiased">
<div class="w-full max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
    <div class="bg-navy p-7 text-center">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-sky-500/20">
            <i data-lucide="lock" class="h-8 w-8 text-sky-400"></i>
        </div>
        <h1 class="mt-4 text-lg font-bold text-white">Sistem Zaten Kurulu</h1>
        <p class="mt-1 text-sm text-slate-400">Güvenlik nedeniyle kurulum sihirbazı tekrar çalıştırılamaz.</p>
    </div>
    <div class="space-y-4 p-6 text-sm">
        <?php if ($installedAt !== '' || $adminEmail !== ''): ?>
            <dl class="space-y-1.5 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                <?php if ($installedAt !== ''): ?><div class="flex items-center justify-between gap-2"><dt class="text-slate-500">Kurulum Tarihi</dt><dd class="font-mono text-xs font-semibold text-slate-900"><?= e($installedAt) ?></dd></div><?php endif; ?>
                <?php if ($adminEmail !== ''): ?><div class="flex items-center justify-between gap-2"><dt class="text-slate-500">Yönetici</dt><dd class="font-mono text-xs font-semibold text-slate-900"><?= e($adminEmail) ?></dd></div><?php endif; ?>
            </dl>
        <?php endif; ?>
        <p class="text-xs leading-relaxed text-slate-500">Kurulumu sıfırdan yapmak istiyorsanız sunucudan <code class="rounded bg-slate-100 px-1 font-mono">storage/installed.lock</code> dosyasını kaldırın ve veritabanını temizleyin.</p>
        <a href="<?= url('login') ?>" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"><i data-lucide="log-in" class="h-4 w-4"></i> Giriş Ekranına Git</a>
    </div>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) { lucide.createIcons(); }</script>
</body>
</html>
