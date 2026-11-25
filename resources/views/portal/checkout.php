<?php
$settings = $settings ?? [];
$user = $user ?? [];
$defaultShip = $addresses[0]['id'] ?? 0;
$defaultBill = $addresses[0]['id'] ?? 0;
$overdue = (bool) ($credit['has_overdue'] ?? false);
$overLimit = ($credit['available'] ?? 0) < $totals['gross'];
$limit = $user['order_approval_limit'] ?? null;
$exceedsUserLimit = $limit !== null && $totals['gross'] > (float) $limit;
?>
<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Sipariş Tamamla</h1>
    <p class="mt-1 text-sm text-slate-500">Sevkiyat/fatura adresinizi ve ödeme yöntemini seçerek siparişi tamamlayın.</p>
</div>

<form action="<?= url('portal/siparis-tamamla') ?>" method="POST">
    <?= csrf_field() ?>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_380px]">
        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="map-pin" class="h-4 w-4 text-action"></i> Adres Bilgileri</h2>
                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="shipping_address_id" class="mb-1 block text-xs font-semibold text-slate-600">Sevkiyat Adresi</label>
                        <select id="shipping_address_id" name="shipping_address_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <?php foreach ($addresses as $addr): ?>
                                <option value="<?= (int) $addr['id'] ?>" <?= (int) $defaultShip === (int) $addr['id'] ? 'selected' : '' ?>><?= e((string) $addr['title']) ?> — <?= e((string) $addr['district']) ?>/<?= e((string) $addr['city']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="billing_address_id" class="mb-1 block text-xs font-semibold text-slate-600">Fatura Adresi</label>
                        <select id="billing_address_id" name="billing_address_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                            <?php foreach ($addresses as $addr): ?>
                                <option value="<?= (int) $addr['id'] ?>" <?= (int) $defaultBill === (int) $addr['id'] ? 'selected' : '' ?>><?= e((string) $addr['title']) ?> — <?= e((string) $addr['district']) ?>/<?= e((string) $addr['city']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="customer_po_number" class="mb-1 block text-xs font-semibold text-slate-600">Müşteri PO Numarası</label>
                        <input type="text" id="customer_po_number" name="customer_po_number" value="<?= e((string) ($cart['po_number'] ?? '')) ?>" placeholder="Opsiyonel" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="customer_note" class="mb-1 block text-xs font-semibold text-slate-600">Sipariş Notu</label>
                        <input type="text" id="customer_note" name="customer_note" value="<?= e((string) ($cart['notes'] ?? '')) ?>" placeholder="Opsiyonel" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="flex items-center gap-2 text-sm font-bold text-slate-800"><i data-lucide="credit-card" class="h-4 w-4 text-action"></i> Ödeme Yöntemi</h2>
                <div class="mt-4 space-y-2.5">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                        <input type="radio" name="payment_method" value="open_account" checked class="mt-0.5 text-action focus:ring-action/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Açık Hesap (Vadeli)</span>
                            <span class="block text-xs text-slate-500">Vade: <?= (int) ($credit['term_days'] ?? 0) ?> gün · Kullanılabilir limit: <?= e(money($credit['available'] ?? 0)) ?></span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                        <input type="radio" name="payment_method" value="bank_transfer" class="mt-0.5 text-action focus:ring-action/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Havale / EFT</span>
                            <span class="block text-xs text-slate-500"><?= e((string) ($settings['bank_name'] ?? 'Banka')) ?> · IBAN: <span class="font-mono"><?= e((string) ($settings['bank_iban'] ?? '-')) ?></span></span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                        <input type="radio" name="payment_method" value="credit_card" class="mt-0.5 text-action focus:ring-action/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Kredi Kartı</span>
                            <span class="block text-xs text-slate-500">Sipariş sonrası güvenli ödeme bağlantısı iletilir.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                        <input type="radio" name="payment_method" value="cheque" class="mt-0.5 text-action focus:ring-action/30">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Çek / Senet</span>
                            <span class="block text-xs text-slate-500">Finans ekibi onayı sonrası işleme alınır.</span>
                        </span>
                    </label>
                </div>

                <?php if ($overdue): ?>
                    <div class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-xs text-red-700">Vadesi geçmiş bakiyeniz bulunduğu için açık hesap siparişleri finans onayına düşecektir.</div>
                <?php elseif ($overLimit): ?>
                    <div class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-700">Sipariş tutarı kullanılabilir risk limitinizi aştığı için açık hesap siparişi finans onayına düşecektir.</div>
                <?php endif; ?>
                <?php if ($exceedsUserLimit): ?>
                    <div class="mt-2 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-700">Harcama limitiniz (<?= e(money($limit)) ?>) aşıldığı için sipariş firma onayına gönderilecektir.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-slate-800">Sipariş Özeti</h2>
            <div class="mt-3 max-h-64 space-y-2 overflow-y-auto pr-1">
                <?php foreach ($items as $item): ?>
                    <div class="flex items-start justify-between gap-2 border-b border-slate-100 pb-2 text-xs">
                        <div class="min-w-0">
                            <div class="line-clamp-1 font-medium text-slate-700"><?= e((string) $item['name']) ?></div>
                            <div class="font-mono text-[10px] text-slate-400"><?= e((string) $item['sku']) ?> × <?= (int) $item['quantity'] ?></div>
                        </div>
                        <span class="shrink-0 font-mono font-semibold text-slate-800"><?= e(money($item['line_total_net'])) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <dl class="mt-4 space-y-2 text-sm">
                <div class="flex justify-between"><dt class="text-slate-500">Ara Toplam</dt><dd class="font-mono font-semibold"><?= e(money($totals['net'])) ?></dd></div>
                <div class="flex justify-between"><dt class="text-slate-500">KDV</dt><dd class="font-mono font-semibold"><?= e(money($totals['vat'])) ?></dd></div>
                <div class="flex justify-between border-t border-slate-100 pt-2"><dt class="font-bold text-slate-900">Genel Toplam</dt><dd class="font-mono text-lg font-bold text-slate-900"><?= e(money($totals['gross'])) ?></dd></div>
            </dl>
            <button type="submit" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-action px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                <i data-lucide="check-circle" class="h-4 w-4"></i> Siparişi Onayla
            </button>
            <a href="<?= url('portal/sepet') ?>" class="mt-2 flex w-full items-center justify-center rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Sepete Dön</a>
        </div>
    </div>
</form>
