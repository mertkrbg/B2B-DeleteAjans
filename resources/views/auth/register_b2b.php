<div class="mb-6 text-center lg:text-left">
    <div class="mb-4 flex justify-center lg:hidden">
        <img src="<?= asset('images/logo.svg') ?>" alt="B2B Enterprise Portal" width="220" height="48" class="h-10 w-auto">
    </div>
    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Yeni Bayi Başvurusu</h2>
    <p class="mt-2 text-sm text-slate-500">Firmanızı B2B tedarik ağımıza katın. Başvurunuz yönetici onayına gönderilir.</p>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-6 auth-card-shadow sm:p-8">
    <form method="POST" action="<?= url('bayi-basvuru') ?>" class="space-y-5" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="title" class="mb-1.5 block text-sm font-semibold text-slate-700">Firma Unvanı <span class="text-red-500">*</span></label>
                <input type="text" id="title" name="title" value="<?= e((string) old('title')) ?>"
                       class="w-full rounded-xl border <?= error_of('title') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="Örn: Anadolu Elektrik Malzemeleri San. Tic. A.Ş." required>
                <?php if (error_of('title')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('title')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="brand_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Marka / Tabela Adı</label>
                <input type="text" id="brand_name" name="brand_name" value="<?= e((string) old('brand_name')) ?>"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="Örn: Anadolu Elektrik">
            </div>

            <div>
                <label for="mersis_no" class="mb-1.5 block text-sm font-semibold text-slate-700">MERSİS No</label>
                <input type="text" id="mersis_no" name="mersis_no" value="<?= e((string) old('mersis_no')) ?>"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="0000000000000000">
            </div>

            <div>
                <label for="tax_office" class="mb-1.5 block text-sm font-semibold text-slate-700">Vergi Dairesi <span class="text-red-500">*</span></label>
                <input type="text" id="tax_office" name="tax_office" value="<?= e((string) old('tax_office')) ?>"
                       class="w-full rounded-xl border <?= error_of('tax_office') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="Örn: Beşiktaş" required>
                <?php if (error_of('tax_office')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('tax_office')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="tax_number" class="mb-1.5 block text-sm font-semibold text-slate-700">VKN / TCKN <span class="text-red-500">*</span></label>
                <input type="text" id="tax_number" name="tax_number" value="<?= e((string) old('tax_number')) ?>" inputmode="numeric"
                       class="w-full rounded-xl border <?= error_of('tax_number') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm tabular-nums focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="1234567890" required>
                <?php if (error_of('tax_number')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('tax_number')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="contact_name" class="mb-1.5 block text-sm font-semibold text-slate-700">Yetkili Ad Soyad <span class="text-red-500">*</span></label>
                <input type="text" id="contact_name" name="contact_name" value="<?= e((string) old('contact_name')) ?>"
                       class="w-full rounded-xl border <?= error_of('contact_name') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="Ad Soyad" required>
                <?php if (error_of('contact_name')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('contact_name')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Telefon <span class="text-red-500">*</span></label>
                <input type="tel" id="phone" name="phone" value="<?= e((string) old('phone')) ?>"
                       class="w-full rounded-xl border <?= error_of('phone') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm tabular-nums focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="0212 000 00 00" required>
                <?php if (error_of('phone')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('phone')) ?></p><?php endif; ?>
            </div>

            <div class="sm:col-span-2">
                <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">E-posta Adresi <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" autocomplete="username"
                       class="w-full rounded-xl border <?= error_of('email') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="yetkili@firma.com" required>
                <?php if (error_of('email')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('email')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="city" class="mb-1.5 block text-sm font-semibold text-slate-700">İl <span class="text-red-500">*</span></label>
                <input type="text" id="city" name="city" value="<?= e((string) old('city')) ?>"
                       class="w-full rounded-xl border <?= error_of('city') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="İstanbul" required>
                <?php if (error_of('city')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('city')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="district" class="mb-1.5 block text-sm font-semibold text-slate-700">İlçe <span class="text-red-500">*</span></label>
                <input type="text" id="district" name="district" value="<?= e((string) old('district')) ?>"
                       class="w-full rounded-xl border <?= error_of('district') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="Başakşehir" required>
                <?php if (error_of('district')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('district')) ?></p><?php endif; ?>
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">Açık Adres <span class="text-red-500">*</span></label>
                <textarea id="address" name="address" rows="3"
                          class="w-full rounded-xl border <?= error_of('address') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                          placeholder="Mahalle, sokak, no..." required><?= e((string) old('address')) ?></textarea>
                <?php if (error_of('address')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('address')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Şifre <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" autocomplete="new-password"
                       class="w-full rounded-xl border <?= error_of('password') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="En az 8 karakter" required>
                <?php if (error_of('password')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('password')) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Şifre Tekrarı <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                       class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm focus:border-action focus:ring-2 focus:ring-action/30"
                       placeholder="Şifreyi tekrar girin" required>
            </div>

            <div class="sm:col-span-2">
                <label for="tax_plate" class="mb-1.5 block text-sm font-semibold text-slate-700">Vergi Levhası <span class="text-red-500">*</span></label>
                <div class="flex items-center justify-center rounded-xl border-2 border-dashed <?= error_of('tax_plate') ? 'border-red-400' : 'border-slate-300' ?> bg-slate-50 px-4 py-6">
                    <div class="text-center">
                        <i data-lucide="upload-cloud" class="mx-auto h-8 w-8 text-slate-400"></i>
                        <div class="mt-2 text-sm text-slate-600">
                            <input type="file" id="tax_plate" name="tax_plate" accept=".pdf,.jpg,.jpeg,.png" class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-action file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-blue-700" required>
                        </div>
                        <p class="mt-2 text-xs text-slate-400">PDF, JPG veya PNG · Maks. 5 MB</p>
                    </div>
                </div>
                <?php if (error_of('tax_plate')): ?><p class="mt-1.5 text-xs font-medium text-red-600"><?= e(error_of('tax_plate')) ?></p><?php endif; ?>
            </div>
        </div>

        <label class="flex items-start gap-2 text-xs text-slate-500">
            <input type="checkbox" required class="mt-0.5 rounded border-slate-300 text-action focus:ring-action/30">
            <span>Kurumsal üyelik sözleşmesini ve KVKK aydınlatma metnini okudum, kabul ediyorum.</span>
        </label>

        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-action px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-action/25 transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-action focus:ring-offset-2">
            <i data-lucide="send" class="h-4 w-4"></i>
            Başvuruyu Gönder
        </button>
    </form>

    <div class="mt-6 border-t border-slate-100 pt-6 text-center">
        <p class="text-sm text-slate-500">
            Zaten hesabınız var mı?
            <a href="<?= url('login') ?>" class="font-semibold text-action hover:text-blue-700">Giriş Yapın</a>
        </p>
    </div>
</div>
