<?php
$status = (string) ($status ?? 'pending');
$config = [
    'pending' => [
        'icon' => 'hourglass', 'tone' => 'amber',
        'title' => 'Başvurunuz İnceleniyor',
        'message' => 'Firma başvurunuz satış ekibimiz tarafından değerlendiriliyor. Onaylandığında tüm B2B sipariş ve fiyat özelliklerine erişebileceksiniz.',
    ],
    'suspended' => [
        'icon' => 'shield-alert', 'tone' => 'red',
        'title' => 'Hesabınız Askıya Alındı',
        'message' => 'Risk limiti veya cari durum nedeniyle hesabınız geçici olarak askıya alınmıştır. Detaylar için lütfen satış temsilcinizle iletişime geçin.',
    ],
    'rejected' => [
        'icon' => 'x-octagon', 'tone' => 'red',
        'title' => 'Başvurunuz Reddedildi',
        'message' => 'Bayi başvurunuz onaylanmadı. Ek bilgi için lütfen destek ekibimizle iletişime geçin.',
    ],
][$status] ?? [
    'icon' => 'info', 'tone' => 'blue',
    'title' => 'Hesap Durumu', 'message' => 'Hesabınız şu anda portal erişimine uygun değil.',
];
$tone = $config['tone'] === 'red' ? 'red' : ($config['tone'] === 'amber' ? 'amber' : 'blue');
$ring = ['red' => 'bg-red-50 text-red-600', 'amber' => 'bg-amber-50 text-amber-600', 'blue' => 'bg-blue-50 text-blue-600'][$tone];
?>
<div class="mx-auto w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 auth-card-shadow sm:p-8">
    <div class="flex flex-col items-center text-center">
        <div class="flex h-16 w-16 items-center justify-center rounded-2xl <?= e($ring) ?>">
            <i data-lucide="<?= e($config['icon']) ?>" class="h-8 w-8"></i>
        </div>
        <h1 class="mt-4 text-xl font-bold text-slate-900"><?= e($config['title']) ?></h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500"><?= e($config['message']) ?></p>

        <div class="mt-5 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-left">
            <div class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Firma</div>
            <div class="text-sm font-semibold text-slate-800"><?= e((string) ($company['title'] ?? '')) ?></div>
            <div class="mt-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">Durum</div>
            <div class="text-sm font-medium text-slate-700">
                <?= e(['pending' => 'Onay Bekliyor', 'suspended' => 'Askıya Alındı', 'rejected' => 'Reddedildi'][$status] ?? 'Bilinmiyor') ?>
            </div>
        </div>

        <div class="mt-6 flex w-full flex-col gap-2 sm:flex-row">
            <a href="<?= url('logout') ?>" class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
                <i data-lucide="log-out" class="h-4 w-4"></i> Çıkış Yap
            </a>
            <a href="mailto:destek@b2b.test" class="flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                <i data-lucide="mail" class="h-4 w-4"></i> Destek
            </a>
        </div>
    </div>
</div>
