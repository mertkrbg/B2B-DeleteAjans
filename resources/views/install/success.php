<?php
$admin = $admin ?? [];
$appUrl = $appUrl ?? url('');
$demoData = $demoData ?? false;
$state = $state ?? [];
?>
<!DOCTYPE html>
<html lang="tr" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Kurulum Tamamlandı')) ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/logo.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: {
            colors: { navy:'#0F172A', action:'#2563EB', canvas:'#F8FAFC', amber:{ DEFAULT:'#D97706' } },
            fontFamily: { sans:['Inter','system-ui','Segoe UI','sans-serif'], mono:['JetBrains Mono','ui-monospace','monospace'] }
        } } }
    </script>
</head>
<body class="flex min-h-screen items-center justify-center bg-canvas p-5 text-slate-900 antialiased">
<div class="w-full max-w-xl">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-xl">
        <div class="bg-navy p-7 text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/20">
                <i data-lucide="check-circle" class="h-9 w-9 text-emerald-400"></i>
            </div>
            <h1 class="mt-4 text-xl font-bold text-white">Kurulum Başarıyla Tamamlandı</h1>
            <p class="mt-1 text-sm text-slate-400">Sistem kullanıma hazır. Tekrar kurulum otomatik olarak engellendi.</p>
        </div>
        <div class="space-y-4 p-6">
            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                <h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">Yönetici Giriş Bilgileri</h2>
                <dl class="mt-2 space-y-1.5 text-sm">
                    <div class="flex items-center justify-between gap-2"><dt class="text-slate-500">E-posta</dt><dd class="font-mono font-semibold text-slate-900"><?= e((string) ($admin['email'] ?? '')) ?></dd></div>
                    <div class="flex items-center justify-between gap-2"><dt class="text-slate-500">Şifre</dt><dd class="font-mono text-slate-500">Kurulumda belirlediğiniz şifre</dd></div>
                    <div class="flex items-center justify-between gap-2"><dt class="text-slate-500">Site Adresi</dt><dd class="font-mono text-xs font-semibold text-slate-900"><?= e($appUrl) ?></dd></div>
                    <div class="flex items-center justify-between gap-2"><dt class="text-slate-500">Örnek Veri</dt><dd class="font-semibold <?= $demoData ? 'text-emerald-700' : 'text-slate-600' ?>"><?= $demoData ? 'Yüklendi' : 'Yüklenmedi' ?></dd></div>
                </dl>
            </div>

            <div class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-800">
                <i data-lucide="shield-alert" class="mt-0.5 h-4 w-4 shrink-0"></i>
                <p class="leading-relaxed"><strong>Güvenlik:</strong> Kurulum kilidi (<code class="font-mono">storage/installed.lock</code>) oluşturuldu. Bu dosyayı silmedikçe kurulum ekranı tekrar çalışmaz. Sunucu erişimini yalnızca yetkili kişilerle paylaşın.</p>
            </div>

            <a href="<?= e(rtrim((string) $appUrl, '/') . '/login') ?>" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-action/20 transition hover:bg-blue-700">
                <i data-lucide="log-in" class="h-4 w-4"></i> Giriş Ekranına Git
            </a>
        </div>
    </div>
    <p class="mt-4 text-center text-xs text-slate-400">B2B Enterprise Portal · Kurulum <?= e((string) ($state['installed_at'] ?? date('Y-m-d H:i:s'))) ?></p>
</div>
<script src="https://unpkg.com/lucide@latest"></script>
<script>if (window.lucide) { lucide.createIcons(); }</script>
</body>
</html>
