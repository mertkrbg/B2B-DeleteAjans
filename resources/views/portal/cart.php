<?php
$totals = $totals ?? ['net' => 0, 'vat' => 0, 'gross' => 0, 'count' => 0];
$cart = $cart ?? [];
$templates = $templates ?? [];
$warehouses = $warehouses ?? [];
?>
<div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Sepetim</h1>
        <p class="mt-1 text-sm text-slate-500"><?= (int) $totals['count'] ?> ürün · fiyatlar bayi iskontoları uygulanmış KDV hariç tutarlardır.</p>
    </div>
    <a href="<?= url('portal/katalog') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="plus" class="h-4 w-4"></i> Alışverişe Devam</a>
</div>

<?php if (empty($items)): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <i data-lucide="shopping-cart" class="mx-auto h-12 w-12 text-slate-300"></i>
        <h2 class="mt-3 text-base font-semibold text-slate-800">Sepetiniz boş</h2>
        <p class="mt-1 text-sm text-slate-500">Katalogdan ürün ekleyin, hızlı siparişi kullanın veya kayıtlı bir şablon yükleyin.</p>
        <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
            <a href="<?= url('portal/katalog') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="layout-grid" class="h-4 w-4"></i> Katalog</a>
            <a href="<?= url('portal/hizli-siparis') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="list-plus" class="h-4 w-4"></i> Hızlı Sipariş</a>
        </div>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[1fr_360px]">
        <div class="space-y-3 md:hidden">
            <?php foreach ($items as $item): ?>
                <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="flex items-start gap-3">
                        <a href="<?= url('portal/urun/' . $item['slug']) ?>" class="h-16 w-20 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                            <img src="<?= asset((string) $item['main_image']) ?>" alt="<?= e((string) $item['name']) ?>" class="h-full w-full object-cover">
                        </a>
                        <div class="min-w-0 flex-1">
                            <a href="<?= url('portal/urun/' . $item['slug']) ?>" class="line-clamp-2 text-sm font-semibold leading-snug text-slate-800"><?= e((string) $item['name']) ?></a>
                            <div class="font-mono text-[11px] text-slate-500"><?= e((string) $item['sku']) ?> · <?= e((string) $item['unit_type']) ?></div>
                            <div class="mt-1 flex items-center justify-between">
                                <span class="font-mono text-xs text-slate-600"><?= e(money($item['unit_net_price'])) ?></span>
                                <span class="font-mono text-sm font-bold text-slate-900"><?= e(money($item['line_total_net'])) ?></span>
                            </div>
                        </div>
                        <form action="<?= url('portal/sepet/sil') ?>" method="POST" onsubmit="return confirm('Bu ürünü sepetten çıkarmak istiyor musunuz?');" class="shrink-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                            <button type="submit" class="flex h-11 w-11 items-center justify-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Ürünü sepetten çıkar"><i data-lucide="trash-2" class="h-5 w-5"></i></button>
                        </form>
                    </div>
                    <form action="<?= url('portal/sepet/guncelle') ?>" method="POST" class="mt-3 space-y-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <div class="flex items-center gap-2">
                            <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="<?= (int) $item['min_order_qty'] ?>" step="<?= (int) $item['order_step_qty'] ?>" aria-label="Adet" class="h-11 w-20 rounded-lg border border-slate-300 px-2 text-right text-sm tabular-nums">
                            <select name="warehouse_id" aria-label="Depo" class="h-11 min-w-0 flex-1 rounded-lg border border-slate-300 px-2 text-xs">
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?= (int) $wh['id'] ?>" <?= (int) $item['warehouse_id'] === (int) $wh['id'] ? 'selected' : '' ?>><?= e((string) $wh['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="flex h-11 items-center gap-1 rounded-lg bg-action px-3 text-xs font-semibold text-white hover:bg-blue-700"><i data-lucide="refresh-cw" class="h-4 w-4"></i> Güncelle</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="hidden overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:block">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Ürün</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Birim Fiyat</th>
                            <th class="px-3 py-2.5 text-center font-semibold">Adet / Depo</th>
                            <th class="px-3 py-2.5 text-right font-semibold">Tutar</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="<?= url('portal/urun/' . $item['slug']) ?>" class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                            <img src="<?= asset((string) $item['main_image']) ?>" alt="<?= e((string) $item['name']) ?>" class="h-full w-full object-cover">
                                        </a>
                                        <div class="min-w-0">
                                            <a href="<?= url('portal/urun/' . $item['slug']) ?>" class="line-clamp-1 font-semibold text-slate-800 hover:text-action"><?= e((string) $item['name']) ?></a>
                                            <div class="font-mono text-[11px] text-slate-500"><?= e((string) $item['sku']) ?> · <?= e((string) $item['unit_type']) ?></div>
                                            <div class="text-[11px] text-emerald-600">İskonto: %<?= e(rtrim(rtrim(number_format((float) $item['applied_discount_rate'], 2, ',', '.'), '0'), ',')) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-right font-mono text-xs text-slate-700"><?= e(money($item['unit_net_price'])) ?></td>
                                <td class="px-3 py-3">
                                    <form action="<?= url('portal/sepet/guncelle') ?>" method="POST" class="flex flex-col items-center gap-1">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <div class="flex items-center gap-1">
                                            <input type="number" name="quantity" value="<?= (int) $item['quantity'] ?>" min="<?= (int) $item['min_order_qty'] ?>" step="<?= (int) $item['order_step_qty'] ?>" aria-label="Adet" class="w-16 rounded-lg border border-slate-300 px-2 py-1 text-right text-xs tabular-nums">
                                            <button type="submit" class="rounded-lg border border-slate-300 p-1 text-slate-600 hover:bg-slate-50" title="Güncelle" aria-label="Adedi güncelle"><i data-lucide="refresh-cw" class="h-3.5 w-3.5"></i></button>
                                        </div>
                                        <select name="warehouse_id" aria-label="Depo" class="w-full max-w-[140px] rounded-lg border border-slate-300 px-1 py-1 text-[11px]">
                                            <?php foreach ($warehouses as $wh): ?>
                                                <option value="<?= (int) $wh['id'] ?>" <?= (int) $item['warehouse_id'] === (int) $wh['id'] ? 'selected' : '' ?>><?= e((string) $wh['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td class="px-3 py-3 text-right font-mono font-semibold text-slate-900"><?= e(money($item['line_total_net'])) ?></td>
                                <td class="px-4 py-3 text-right">
                                    <form action="<?= url('portal/sepet/sil') ?>" method="POST" onsubmit="return confirm('Bu ürünü sepetten çıkarmak istiyor musunuz?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                                        <button type="submit" class="rounded-lg p-1.5 text-slate-400 hover:bg-red-50 hover:text-red-600" aria-label="Ürünü sepetten çıkar"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">Sipariş Özeti</h2>
                <dl class="mt-4 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Ara Toplam (KDV Hariç)</dt><dd class="font-mono font-semibold text-slate-800"><?= e(money($totals['net'])) ?></dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">KDV</dt><dd class="font-mono font-semibold text-slate-800"><?= e(money($totals['vat'])) ?></dd></div>
                    <div class="flex justify-between border-t border-slate-100 pt-3"><dt class="text-base font-bold text-slate-900">Genel Toplam</dt><dd class="font-mono text-lg font-bold text-slate-900"><?= e(money($totals['gross'])) ?></dd></div>
                </dl>
                <div class="mt-3 rounded-xl <?= ($credit['available'] ?? 0) < $totals['gross'] ? 'bg-amber-50 text-amber-700' : 'bg-emerald-50 text-emerald-700' ?> px-3 py-2 text-xs">
                    Kullanılabilir limit: <span class="font-mono font-semibold"><?= e(money($credit['available'] ?? 0)) ?></span>
                    <?php if (($credit['has_overdue'] ?? false)): ?><span class="block font-semibold text-red-600">Vadesi geçmiş bakiyeniz var.</span><?php endif; ?>
                </div>
                <a href="<?= url('portal/siparis-tamamla') ?>" class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-action px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="credit-card" class="h-4 w-4"></i> Siparişi Tamamla</a>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">Sepet Ayarları</h2>
                <form action="<?= url('portal/sepet/ayarlar') ?>" method="POST" class="mt-3 space-y-2">
                    <?= csrf_field() ?>
                    <div>
                        <label for="po_number" class="mb-1 block text-xs font-semibold text-slate-600">Müşteri PO Numarası</label>
                        <input type="text" id="po_number" name="po_number" value="<?= e((string) ($cart['po_number'] ?? '')) ?>" placeholder="Örn: PO-2026-0099" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="notes" class="mb-1 block text-xs font-semibold text-slate-600">Sipariş Notu</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= e((string) ($cart['notes'] ?? '')) ?></textarea>
                    </div>
                    <button type="submit" class="w-full rounded-lg bg-navy px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">Ayarları Kaydet</button>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="text-sm font-bold text-slate-800">Sepeti Şablon Olarak Kaydet</h2>
                <form action="<?= url('portal/sepet/sablon-kaydet') ?>" method="POST" class="mt-3 flex gap-1.5">
                    <?= csrf_field() ?>
                    <input type="text" name="template_name" placeholder="Şablon adı (ör. Aylık Sarf)" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="rounded-lg bg-action px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Kaydet</button>
                </form>
                <p class="mt-2 text-[11px] text-slate-400">Mevcut sepet kalemleri şablon olarak saklanır, sonra tek tıkla geri yüklenebilir.</p>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($templates)): ?>
    <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Kayıtlı Sepet Şablonları</h2>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            <?php foreach ($templates as $tpl): ?>
                <div class="flex items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="min-w-0">
                        <div class="truncate text-sm font-semibold text-slate-800"><?= e((string) $tpl['cart_name']) ?></div>
                        <div class="text-[11px] text-slate-500"><?= (int) $tpl['item_count'] ?> kalem · <?= e(money($tpl['total_net'])) ?> · <?= e(tr_date((string) $tpl['updated_at'])) ?></div>
                    </div>
                    <form action="<?= url('portal/sepet/sablon-yukle') ?>" method="POST" class="shrink-0">
                        <?= csrf_field() ?>
                        <input type="hidden" name="template_id" value="<?= (int) $tpl['id'] ?>">
                        <button type="submit" class="rounded-lg bg-action px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-700">Yükle</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
