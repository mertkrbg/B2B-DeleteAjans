<?php
$isEdit = $product !== null;
$action = $isEdit ? url('admin/urunler/' . $product['id']) : url('admin/urunler');
$tierRows = [];
foreach (array_slice($tiers ?? [], 0, 4) as $t) {
    $tierRows[] = $t;
}
while (count($tierRows) < 4) {
    $tierRows[] = ['min_qty' => '', 'max_qty' => '', 'discount_rate' => ''];
}
?>
<nav class="mb-4 flex items-center gap-1.5 text-xs text-slate-500"><a href="<?= url('admin/urunler') ?>" class="hover:text-action">Ürünler</a><i data-lucide="chevron-right" class="h-3.5 w-3.5"></i><span class="font-medium text-slate-700"><?= $isEdit ? e((string) $product['sku']) : 'Yeni Ürün' ?></span></nav>

<div class="mb-4"><h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl"><?= $isEdit ? e((string) $product['name']) : 'Yeni Ürün Oluştur' ?></h1></div>

<form action="<?= $action ?>" method="POST" class="space-y-4">
    <?= csrf_field() ?>
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Ürün Bilgileri</h2>
        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="sm:col-span-2"><label class="mb-1 block text-xs font-semibold text-slate-600">Ürün Adı <span class="text-red-500">*</span></label><input type="text" name="name" value="<?= e((string) ($product['name'] ?? '')) ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Stok Kodu (SKU) <span class="text-red-500">*</span></label><input type="text" name="sku" value="<?= e((string) ($product['sku'] ?? '')) ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Kategori <span class="text-red-500">*</span></label><select name="category_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><option value="0">Seçiniz</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>" <?= (int) ($product['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>><?= e((string) $c['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Marka</label><select name="brand_id" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><option value="0">Markasız</option><?php foreach ($brands as $b): ?><option value="<?= (int) $b['id'] ?>" <?= (int) ($product['brand_id'] ?? 0) === (int) $b['id'] ? 'selected' : '' ?>><?= e((string) $b['name']) ?></option><?php endforeach; ?></select></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Birim Tipi</label><select name="unit_type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><?php foreach (['adet','kutu','koli','metre','kg','set'] as $ut): ?><option value="<?= $ut ?>" <?= ($product['unit_type'] ?? 'adet') === $ut ? 'selected' : '' ?>><?= ucfirst($ut) ?></option><?php endforeach; ?></select></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Liste Fiyatı <span class="text-red-500">*</span></label><input type="number" step="0.0001" min="0.0001" name="base_list_price" value="<?= e((string) ($product['base_list_price'] ?? '')) ?>" required class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Para Birimi</label><select name="currency" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><?php foreach (['TRY','USD','EUR'] as $cur): ?><option value="<?= $cur ?>" <?= ($product['currency'] ?? 'TRY') === $cur ? 'selected' : '' ?>><?= $cur ?></option><?php endforeach; ?></select></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">KDV (%)</label><input type="number" step="0.01" min="0" name="vat_rate" value="<?= e((string) ($product['vat_rate'] ?? '20')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Koli İçi Adet</label><input type="number" min="1" name="box_quantity" value="<?= (int) ($product['box_quantity'] ?? 1) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Minimum Sipariş</label><input type="number" min="1" name="min_order_qty" value="<?= (int) ($product['min_order_qty'] ?? 1) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Sipariş Katı (Step)</label><input type="number" min="1" name="order_step_qty" value="<?= (int) ($product['order_step_qty'] ?? 1) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Desi</label><input type="number" step="0.01" min="0.01" name="desi" value="<?= e((string) ($product['desi'] ?? '1')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm tabular-nums"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">Barkod</label><input type="text" name="barcode" value="<?= e((string) ($product['barcode'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm"></div>
            <div><label class="mb-1 block text-xs font-semibold text-slate-600">OEM Kodu</label><input type="text" name="oem_code" value="<?= e((string) ($product['oem_code'] ?? '')) ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-sm"></div>
            <div class="sm:col-span-2 lg:col-span-3"><label class="mb-1 block text-xs font-semibold text-slate-600">Kısa Açıklama</label><textarea name="short_description" rows="2" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><?= e((string) ($product['short_description'] ?? '')) ?></textarea></div>
        </div>
        <div class="mt-4 flex flex-wrap gap-5">
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_featured" value="1" <?= (int) ($product['is_featured'] ?? 0) === 1 ? 'checked' : '' ?> class="rounded border-slate-300 text-action focus:ring-action/30"> Öne çıkan</label>
            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" <?= $isEdit ? ((int) $product['is_active'] === 1 ? 'checked' : '') : 'checked' ?> class="rounded border-slate-300 text-action focus:ring-action/30"> Aktif</label>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Çoklu Depo Stokları</h2>
        <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <?php foreach ($warehouses as $w): ?>
                <?php $st = $stocks[(int) $w['id']] ?? null; ?>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="text-xs font-semibold text-slate-700"><?= e((string) $w['name']) ?></div>
                    <div class="text-[10px] text-slate-400"><?= e((string) $w['code']) ?></div>
                    <label class="mt-2 block text-[11px] text-slate-500">Fiziksel Stok</label>
                    <input type="number" min="0" name="stock[<?= (int) $w['id'] ?>]" value="<?= (int) ($st['physical_qty'] ?? 0) ?>" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm tabular-nums">
                    <div class="mt-1 text-[10px] text-slate-400">Rezerve: <?= (int) ($st['reserved_qty'] ?? 0) ?> · Yolda: <?= (int) ($st['incoming_qty'] ?? 0) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Kademeli İskontolar</h2>
        <p class="text-xs text-slate-500">Adet aralığına göre ek iskonto. Boş bırakılan satırlar yok sayılır.</p>
        <div class="mt-3 overflow-x-auto">
            <table class="w-full min-w-[520px] text-left text-sm">
                <thead class="text-[10px] uppercase tracking-wide text-slate-400"><tr><th class="py-1.5 font-semibold">Min Adet</th><th class="py-1.5 font-semibold">Max Adet (boş=üzeri)</th><th class="py-1.5 font-semibold">İskonto %</th></tr></thead>
                <tbody>
                    <?php foreach ($tierRows as $t): ?>
                        <tr>
                            <td class="py-1.5 pr-2"><input type="number" min="0" name="tier_min[]" value="<?= $t['min_qty'] !== '' ? (int) $t['min_qty'] : '' ?>" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm tabular-nums"></td>
                            <td class="py-1.5 pr-2"><input type="number" min="0" name="tier_max[]" value="<?= $t['max_qty'] !== null && $t['max_qty'] !== '' ? (int) $t['max_qty'] : '' ?>" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm tabular-nums"></td>
                            <td class="py-1.5"><input type="number" step="0.01" min="0" max="100" name="tier_rate[]" value="<?= $t['discount_rate'] !== '' ? e(number_format((float) $t['discount_rate'], 2, '.', '')) : '' ?>" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm tabular-nums"></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="flex items-center justify-end gap-2">
        <a href="<?= url('admin/urunler') ?>" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">İptal</a>
        <button type="submit" class="rounded-xl bg-action px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><?= $isEdit ? 'Ürünü Güncelle' : 'Ürünü Oluştur' ?></button>
    </div>
</form>
