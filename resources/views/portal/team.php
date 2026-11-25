<?php
$teamUsers = $teamUsers ?? [];
$subRoles = $subRoles ?? ['purchasing_manager', 'buyer', 'accountant'];
$roleOptions = ['purchasing_manager' => 'Satın Alma Müdürü', 'buyer' => 'Satın Alma Personeli', 'accountant' => 'Muhasebeci'];
?>
<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Firma & Ekip Yönetimi</h1>
    <p class="mt-1 text-sm text-slate-500">Alt kullanıcı ekleyin, rollerini ve harcama limitlerini belirleyin, fiyat/finans görme yetkilerini yönetin.</p>
</div>

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="user-plus" class="h-4 w-4 text-action"></i> Yeni Alt Kullanıcı Ekle</h2>
    <form action="<?= url('portal/ekip') ?>" method="POST" class="mt-4 space-y-4">
        <?= csrf_field() ?>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div>
                <label for="full_name" class="mb-1 block text-xs font-semibold text-slate-600">Ad Soyad <span class="text-red-500">*</span></label>
                <input type="text" id="full_name" name="full_name" value="<?= e((string) old('full_name')) ?>" class="w-full rounded-xl border <?= error_of('full_name') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm" required>
                <?php if (error_of('full_name')): ?><p class="mt-1 text-xs text-red-600"><?= e(error_of('full_name')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="email" class="mb-1 block text-xs font-semibold text-slate-600">E-posta <span class="text-red-500">*</span></label>
                <input type="email" id="email" name="email" value="<?= e((string) old('email')) ?>" class="w-full rounded-xl border <?= error_of('email') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm" required>
                <?php if (error_of('email')): ?><p class="mt-1 text-xs text-red-600"><?= e(error_of('email')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="phone" class="mb-1 block text-xs font-semibold text-slate-600">Telefon</label>
                <input type="text" id="phone" name="phone" value="<?= e((string) old('phone')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="role" class="mb-1 block text-xs font-semibold text-slate-600">Rol</label>
                <select id="role" name="role" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <?php foreach ($subRoles as $r): ?><option value="<?= e($r) ?>" <?= old('role') === $r ? 'selected' : '' ?>><?= e($roleOptions[$r] ?? $r) ?></option><?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="order_approval_limit" class="mb-1 block text-xs font-semibold text-slate-600">Sipariş Onay Limiti (₺)</label>
                <input type="number" step="0.01" min="0" id="order_approval_limit" name="order_approval_limit" placeholder="Limitsiz için boş bırakın" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums">
            </div>
            <div class="flex items-end">
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="can_view_prices" value="1" checked class="rounded border-slate-300 text-action focus:ring-action/30"> Fiyat görsün</label>
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="can_view_finance" value="1" checked class="rounded border-slate-300 text-action focus:ring-action/30"> Finans görsün</label>
                </div>
            </div>
            <div>
                <label for="password" class="mb-1 block text-xs font-semibold text-slate-600">Geçici Şifre <span class="text-red-500">*</span></label>
                <input type="password" id="password" name="password" class="w-full rounded-xl border <?= error_of('password') ? 'border-red-400' : 'border-slate-300' ?> px-3 py-2 text-sm" required>
                <?php if (error_of('password')): ?><p class="mt-1 text-xs text-red-600"><?= e(error_of('password')) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="password_confirmation" class="mb-1 block text-xs font-semibold text-slate-600">Şifre Tekrarı <span class="text-red-500">*</span></label>
                <input type="password" id="password_confirmation" name="password_confirmation" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" required>
            </div>
            <div class="flex items-end">
                <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="plus" class="h-4 w-4"></i> Kullanıcı Ekle</button>
            </div>
        </div>
    </form>
</div>

<div class="mt-4">
    <h2 class="mb-3 text-sm font-bold text-slate-800">Ekip Üyeleri (<?= count($teamUsers) ?>)</h2>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
        <?php foreach ($teamUsers as $u): ?>
            <?php $isOwner = $u['role'] === 'company_owner'; ?>
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl <?= $isOwner ? 'bg-corporate' : 'bg-slate-700' ?> text-sm font-bold text-white"><?= e(mb_substr((string) $u['full_name'], 0, 1)) ?></span>
                        <div>
                            <div class="text-sm font-semibold text-slate-800"><?= e((string) $u['full_name']) ?></div>
                            <div class="text-xs text-slate-500"><?= e((string) $u['email']) ?></div>
                        </div>
                    </div>
                    <div class="flex flex-col items-end gap-1">
                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-semibold <?= $isOwner ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-slate-100 text-slate-600 border-slate-200' ?>"><?= e(role_label((string) $u['role'])) ?></span>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold <?= (int) $u['is_active'] === 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $u['is_active'] === 1 ? 'Aktif' : 'Pasif' ?></span>
                    </div>
                </div>

                <?php if ($isOwner): ?>
                    <div class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-[11px] text-slate-500">Firma sahibi hesabı. Son giriş: <?= $u['last_login_at'] !== null ? e(tr_date((string) $u['last_login_at'], true)) : '—' ?></div>
                <?php else: ?>
                    <form action="<?= url('portal/ekip/guncelle') ?>" method="POST" class="mt-3 space-y-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-slate-600">Ad Soyad</label>
                                <input type="text" name="full_name" value="<?= e((string) $u['full_name']) ?>" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-slate-600">Telefon</label>
                                <input type="text" name="phone" value="<?= e((string) ($u['phone'] ?? '')) ?>" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-slate-600">Rol</label>
                                <select name="role" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm">
                                    <?php foreach ($subRoles as $r): ?><option value="<?= e($r) ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= e($roleOptions[$r] ?? $r) ?></option><?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-semibold text-slate-600">Onay Limiti (₺)</label>
                                <input type="number" step="0.01" min="0" name="order_approval_limit" value="<?= $u['order_approval_limit'] !== null ? e(number_format((float) $u['order_approval_limit'], 2, '.', '')) : '' ?>" placeholder="Limitsiz" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm tabular-nums">
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="checkbox" name="can_view_prices" value="1" <?= (int) $u['can_view_prices'] === 1 ? 'checked' : '' ?> class="rounded border-slate-300 text-action focus:ring-action/30"> Fiyat görsün</label>
                            <label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="checkbox" name="can_view_finance" value="1" <?= (int) $u['can_view_finance'] === 1 ? 'checked' : '' ?> class="rounded border-slate-300 text-action focus:ring-action/30"> Finans görsün</label>
                            <label class="flex items-center gap-1.5 text-xs text-slate-600"><input type="checkbox" name="is_active" value="1" <?= (int) $u['is_active'] === 1 ? 'checked' : '' ?> class="rounded border-slate-300 text-action focus:ring-action/30"> Aktif</label>
                            <button type="submit" class="ml-auto rounded-lg bg-navy px-3 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">Kaydet</button>
                        </div>
                        <div class="text-[10px] text-slate-400">Son giriş: <?= $u['last_login_at'] !== null ? e(tr_date((string) $u['last_login_at'], true)) : '—' ?></div>
                    </form>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
