<div class="mb-8 text-center lg:text-left">
    <div class="mb-4 flex justify-center lg:hidden">
        <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise Portal" width="220" height="48" class="h-10 w-auto">
    </div>
    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Hesabınıza Giriş Yapın</h2>
    <p class="mt-2 text-sm text-slate-500">Kurumsal B2B portalına erişmek için giriş bilgilerinizi kullanın.</p>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 auth-card-shadow sm:p-8" x-data="{ show: false }">
    <form method="POST" action="<?= url('login') ?>" class="space-y-5" novalidate>
        <?= csrf_field() ?>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">E-posta Adresi</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i data-lucide="mail" class="h-5 w-5"></i>
                </span>
                <input type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" autocomplete="username"
                       class="w-full rounded-xl border <?= error_of('email') ? 'border-red-400' : 'border-slate-300' ?> bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 placeholder-slate-400 focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="ornek@firma.com" required>
            </div>
            <?php if (error_of('email')): ?>
                <p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('email')) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="password" class="block text-sm font-semibold text-slate-700">Şifre</label>
                <span class="text-xs text-slate-400">Güvenli Giriş</span>
            </div>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <i data-lucide="lock" class="h-5 w-5"></i>
                </span>
                <input type="password" x-bind:type="show ? 'text' : 'password'" id="password" name="password" autocomplete="current-password"
                       class="w-full rounded-xl border <?= error_of('password') ? 'border-red-400' : 'border-slate-300' ?> bg-white py-2.5 pl-10 pr-11 text-sm text-slate-900 placeholder-slate-400 focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="••••••••" required>
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-600" aria-label="Şifreyi göster">
                    <span x-show="!show" class="block">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    </span>
                    <span x-show="show" x-cloak class="block">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.5 13.5 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                    </span>
                </button>
            </div>
            <?php if (error_of('password')): ?>
                <p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('password')) ?></p>
            <?php endif; ?>
        </div>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-action/25 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-action focus:ring-offset-2">
            <i data-lucide="log-in" class="h-4 w-4"></i>
            Giriş Yap
        </button>
    </form>

    <div class="mt-6 border-t border-slate-100 pt-6 text-center">
        <p class="text-sm text-slate-500">
            Henüz bayimiz değil misiniz?
            <a href="<?= url('bayi-basvuru') ?>" class="font-semibold text-action hover:text-blue-700">Bayi Başvurusu Yapın</a>
        </p>
    </div>
</div>

<div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Demo Test Hesapları</p>
    <div class="mt-2 grid grid-cols-1 gap-1.5 text-xs text-slate-600 sm:grid-cols-2">
        <span class="tabular-nums">Admin: admin@b2b.test</span>
        <span class="tabular-nums">Bayi: bayi@anadoluelektrik.test</span>
    </div>
    <p class="mt-1.5 text-[11px] text-slate-400">Şifreler dokümantasyon (docs/06) içinde tanımlıdır.</p>
</div>
