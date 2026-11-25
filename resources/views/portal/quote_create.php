<?php $items = $items ?? []; ?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500">
    <a href="<?= url('portal/teklifler') ?>" class="hover:text-action">Tekliflerim</a>
    <i data-lucide="chevron-right" class="h-3.5 w-3.5"></i>
    <span class="font-medium text-slate-700">Yeni Teklif Talebi</span>
</nav>

<div class="mb-4">
    <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Teklif Talebi Oluştur</h1>
    <p class="mt-1 text-sm text-slate-500">Sepetinizdeki ürünler için hedef fiyat belirterek özel teklif isteyin.</p>
</div>

<?php if (empty($items)): ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center shadow-sm">
        <i data-lucide="shopping-cart" class="mx-auto h-12 w-12 text-slate-300"></i>
        <h2 class="mt-3 text-base font-semibold text-slate-800">Teklif için sepetinize ürün ekleyin</h2>
        <p class="mt-1 text-sm text-slate-500">Teklif talebi sepetinizdeki kalemler üzerinden oluşturulur.</p>
        <div class="mt-4 flex flex-col justify-center gap-2 sm:flex-row">
            <a href="<?= url('portal/katalog') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="layout-grid" class="h-4 w-4"></i> Katalog</a>
            <a href="<?= url('portal/hizli-siparis') ?>" class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="list-plus" class="h-4 w-4"></i> Hızlı Sipariş</a>
        </div>
    </div>
<?php else: ?>
    <form action="<?= url('portal/teklif-olustur') ?>" method="POST">
        <?= csrf_field() ?>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-4 py-3"><h2 class="text-sm font-bold text-slate-800">Sepet Kalemleri</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-3 py-2 font-semibold">Dahil</th>
                            <th class="px-3 py-2 font-semibold">Ürün</th>
                            <th class="px-3 py-2 text-right font-semibold">Güncel Net</th>
                            <th class="px-3 py-2 text-right font-semibold">Adet</th>
                            <th class="px-3 py-2 text-right font-semibold">Hedef Birim Fiyat</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td class="px-3 py-3"><input type="checkbox" name="include[]" value="<?= (int) $item['product_id'] ?>" checked class="rounded border-slate-300 text-action focus:ring-action/30"></td>
                                <td class="px-3 py-3"><div class="font-medium text-slate-800"><?= e((string) $item['name']) ?></div><div class="font-mono text-[11px] text-slate-500"><?= e((string) $item['sku']) ?> · <?= e((string) $item['unit_type']) ?></div></td>
                                <td class="px-3 py-3 text-right font-mono text-xs text-slate-700"><?= e(money($item['unit_net_price'])) ?></td>
                                <td class="px-3 py-3 text-right"><input type="number" name="quantity[<?= (int) $item['product_id'] ?>]" value="<?= (int) $item['quantity'] ?>" min="1" aria-label="Adet" class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-xs tabular-nums"></td>
                                <td class="px-3 py-3 text-right"><input type="number" step="0.01" min="0" name="requested_price[<?= (int) $item['product_id'] ?>]" placeholder="<?= e(number_format((float) $item['unit_net_price'], 2, '.', '')) ?>" aria-label="Hedef fiyat" class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-right text-xs tabular-nums"></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <label for="customer_note" class="mb-1 block text-xs font-semibold text-slate-600">Teklif Notu</label>
            <textarea id="customer_note" name="customer_note" rows="3" placeholder="Proje detayları, termin beklentisi vb." class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></textarea>
            <div class="mt-3 flex flex-col gap-2 sm:flex-row sm:justify-end">
                <a href="<?= url('portal/sepet') ?>" class="rounded-xl border border-slate-300 px-4 py-2.5 text-center text-sm font-semibold text-slate-700 hover:bg-slate-50">Sepete Dön</a>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-action px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="send" class="h-4 w-4"></i> Teklif Talebi Gönder</button>
            </div>
        </div>
    </form>
<?php endif; ?>
