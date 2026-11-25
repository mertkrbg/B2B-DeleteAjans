<?php
$filters = $filters ?? [];
$buildUrl = static function (array $overrides) use ($queryBase): string {
    $params = array_merge($queryBase, $overrides);
    foreach ($params as $k => $v) {
        if ($v === null || $v === '') { unset($params[$k]); }
    }
    return url('portal/katalog') . '?' . http_build_query($params);
};
$curSymbol = static fn (string $c): string => $c === 'EUR' ? '€' : ($c === 'USD' ? '$' : '₺');
?>
<div x-data="{ filtersOpen: false }">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Ürün Kataloğu</h1>
            <p class="mt-1 text-sm text-slate-500"><span class="font-mono font-semibold text-slate-700"><?= (int) $total ?></span> ürün listeleniyor · bayi özel fiyatları uygulanmıştır</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="filtersOpen = !filtersOpen" :aria-expanded="filtersOpen" aria-controls="filterPanel" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 lg:hidden">
                <i data-lucide="sliders-horizontal" class="h-4 w-4"></i> Filtrele
            </button>
            <div class="inline-flex items-center rounded-xl border border-slate-300 bg-white p-0.5">
                <a href="<?= e($buildUrl(['gorunum' => 'card'])) ?>" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold <?= $filters['gorunum'] === 'card' ? 'bg-action text-white' : 'text-slate-600 hover:bg-slate-50' ?>"><i data-lucide="layout-grid" class="h-4 w-4"></i> Kart</a>
                <a href="<?= e($buildUrl(['gorunum' => 'tablo'])) ?>" class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold <?= $filters['gorunum'] === 'table' ? 'bg-action text-white' : 'text-slate-600 hover:bg-slate-50' ?>"><i data-lucide="table" class="h-4 w-4"></i> Tablo</a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[260px_1fr]">
        <aside :class="filtersOpen ? 'block' : 'hidden lg:block'">
            <form id="filterPanel" action="<?= url('portal/katalog') ?>" method="GET" class="space-y-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center justify-between lg:hidden">
                    <span class="text-sm font-bold text-slate-800">Filtreler</span>
                    <button type="button" @click="filtersOpen=false" aria-label="Filtreleri kapat" class="rounded-lg p-1.5 text-slate-500 hover:bg-slate-100"><i data-lucide="x" class="h-4 w-4"></i></button>
                </div>
                <input type="hidden" name="gorunum" value="<?= e($filters['gorunum']) ?>">
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Arama</label>
                    <input type="search" name="q" value="<?= e($filters['q']) ?>" placeholder="SKU / ürün adı" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-action focus:ring-2 focus:ring-action/20">
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Kategori</label>
                    <select name="kategori" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-action focus:ring-2 focus:ring-action/20">
                        <option value="">Tüm Kategoriler</option>
                        <?php foreach ($categories as $cat): ?>
                            <?php if (empty($cat['children'])): ?>
                                <option value="<?= e($cat['slug']) ?>" <?= $filters['kategori'] === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                            <?php else: ?>
                                <optgroup label="<?= e($cat['name']) ?>">
                                    <?php foreach ($cat['children'] as $child): ?>
                                        <option value="<?= e($child['slug']) ?>" <?= $filters['kategori'] === $child['slug'] ? 'selected' : '' ?>><?= e($child['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Marka</label>
                    <select name="marka" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-action focus:ring-2 focus:ring-action/20">
                        <option value="">Tüm Markalar</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= (int) $brand['id'] ?>" <?= (int) $filters['marka'] === (int) $brand['id'] ? 'selected' : '' ?>><?= e($brand['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Sıralama</label>
                    <select name="sirala" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm focus:border-action focus:ring-2 focus:ring-action/20">
                        <option value="featured" <?= $filters['sirala'] === 'featured' ? 'selected' : '' ?>>Öne Çıkanlar</option>
                        <option value="price_asc" <?= $filters['sirala'] === 'price_asc' ? 'selected' : '' ?>>Fiyat (Artan)</option>
                        <option value="price_desc" <?= $filters['sirala'] === 'price_desc' ? 'selected' : '' ?>>Fiyat (Azalan)</option>
                        <option value="name" <?= $filters['sirala'] === 'name' ? 'selected' : '' ?>>Ürün Adı (A-Z)</option>
                        <option value="newest" <?= $filters['sirala'] === 'newest' ? 'selected' : '' ?>>En Yeni</option>
                    </select>
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="stok" value="1" <?= $filters['stok'] ? 'checked' : '' ?> class="rounded border-slate-300 text-action focus:ring-action/30">
                    Sadece stokta olanlar
                </label>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700">Uygula</button>
                    <a href="<?= url('portal/katalog') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">Sıfırla</a>
                </div>
            </form>
        </aside>

        <section>
            <?php if (empty($products)): ?>
                <div class="rounded-2xl border border-slate-200 bg-white p-10 text-center">
                    <i data-lucide="package-search" class="mx-auto h-10 w-10 text-slate-400"></i>
                    <p class="mt-3 text-sm font-medium text-slate-700">Arama kriterlerinize uygun ürün bulunamadı.</p>
                    <a href="<?= url('portal/katalog') ?>" class="mt-3 inline-block text-sm font-semibold text-action">Filtreleri temizle</a>
                </div>
            <?php elseif ($filters['gorunum'] === 'table'): ?>
                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[900px] text-left text-sm">
                            <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-4 py-3 font-semibold">Ürün</th>
                                    <th class="px-3 py-3 font-semibold">Kategori</th>
                                    <th class="px-3 py-3 text-right font-semibold">Stok</th>
                                    <th class="px-3 py-3 text-right font-semibold">İskonto</th>
                                    <th class="px-3 py-3 text-right font-semibold">Liste</th>
                                    <th class="px-3 py-3 text-right font-semibold">Net (KDV Hariç)</th>
                                    <th class="px-3 py-3 text-right font-semibold">KDV Dahil</th>
                                    <th class="px-4 py-3 text-right font-semibold">İşlem</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($products as $p): ?>
                                    <?php $price = $p['price']; $maxTier = 0.0; foreach ($price['tiers'] as $t) { $maxTier = max($maxTier, (float) $t['discount_rate']); } $oos = (int) $p['stock'] <= 0; ?>
                                    <tr class="hover:bg-slate-50/60">
                                        <td class="px-4 py-3">
                                            <div class="flex items-center gap-3">
                                                <a href="<?= url('portal/urun/' . $p['slug']) ?>" class="h-12 w-16 shrink-0 overflow-hidden rounded-lg bg-slate-100">
                                                    <img src="<?= asset((string) $p['main_image']) ?>" alt="<?= e((string) $p['name']) ?>" loading="lazy" class="h-full w-full object-cover">
                                                </a>
                                                <div class="min-w-0">
                                                    <a href="<?= url('portal/urun/' . $p['slug']) ?>" class="line-clamp-1 font-semibold text-slate-800 hover:text-action"><?= e((string) $p['name']) ?></a>
                                                    <div class="font-mono text-[11px] text-slate-500"><?= e((string) $p['sku']) ?> · <?= e((string) ($p['brand_name'] ?? '')) ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 text-xs text-slate-600"><?= e((string) ($p['category_name'] ?? '-')) ?></td>
                                        <td class="px-3 py-3 text-right"><span class="rounded-full px-2 py-0.5 text-[11px] font-semibold <?= (int) $p['stock'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $p['stock'] > 0 ? (int) $p['stock'] . ' ' . e((string) $p['unit_type']) : 'Stok Yok' ?></span></td>
                                        <td class="px-3 py-3 text-right font-mono text-xs <?= $maxTier > 0 ? 'text-amber' : 'text-slate-400' ?>"><?= $maxTier > 0 ? '%' . e(rtrim(rtrim(number_format($maxTier, 2, ',', '.'), '0'), ',')) : '-' ?></td>
                                        <td class="px-3 py-3 text-right font-mono text-xs text-slate-400 line-through"><?= e(money($price['base_list_price'], $curSymbol($price['currency']))) ?></td>
                                        <td class="px-3 py-3 text-right font-mono font-semibold text-slate-900"><?= e(money($price['unit_net'])) ?></td>
                                        <td class="px-3 py-3 text-right font-mono text-xs text-slate-500"><?= e(money($price['unit_gross'])) ?></td>
                                        <td class="px-4 py-3">
                                            <form action="<?= url('portal/sepet/ekle') ?>" method="POST" class="flex items-center justify-end gap-1.5">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                                <input type="number" name="quantity" value="<?= (int) $p['min_order_qty'] ?>" min="<?= (int) $p['min_order_qty'] ?>" step="<?= (int) $p['order_step_qty'] ?>" aria-label="Adet" <?= $oos ? 'disabled' : '' ?> class="w-16 rounded-lg border border-slate-300 px-2 py-1 text-right text-xs tabular-nums disabled:bg-slate-100">
                                                <button type="submit" aria-label="Sepete Ekle: <?= e((string) $p['sku']) ?>" <?= $oos ? 'disabled' : '' ?> class="rounded-lg p-1.5 text-white <?= $oos ? 'cursor-not-allowed bg-slate-300' : 'bg-action hover:bg-blue-700' ?>"><i data-lucide="plus" class="h-4 w-4"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <?php foreach ($products as $p): ?>
                        <?php $price = $p['price']; $maxTier = 0.0; foreach ($price['tiers'] as $t) { $maxTier = max($maxTier, (float) $t['discount_rate']); } $oos = (int) $p['stock'] <= 0; ?>
                        <div class="flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:shadow-md">
                            <a href="<?= url('portal/urun/' . $p['slug']) ?>" class="relative block aspect-[4/3] w-full overflow-hidden bg-slate-100">
                                <img src="<?= asset((string) $p['main_image']) ?>" alt="<?= e((string) $p['name']) ?>" loading="lazy" class="h-full w-full object-cover">
                                <?php if ($maxTier > 0): ?>
                                    <span class="absolute left-2 top-2 rounded-full bg-amber px-2 py-0.5 text-[10px] font-bold text-white">%<?= e(rtrim(rtrim(number_format($maxTier, 2, ',', '.'), '0'), ',')) ?> seviyesine kadar iskonto</span>
                                <?php endif; ?>
                            </a>
                            <div class="flex flex-1 flex-col p-4">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="font-mono font-medium text-slate-500"><?= e((string) $p['sku']) ?></span>
                                    <span class="rounded-full px-2 py-0.5 font-semibold <?= (int) $p['stock'] > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>"><?= (int) $p['stock'] > 0 ? 'Stokta: ' . (int) $p['stock'] : 'Stok Yok' ?></span>
                                </div>
                                <a href="<?= url('portal/urun/' . $p['slug']) ?>" class="mt-1.5 line-clamp-2 min-h-[2.75rem] text-sm font-semibold leading-snug text-slate-800 hover:text-action"><?= e((string) $p['name']) ?></a>
                                <div class="mt-1 text-[11px] text-slate-400"><?= e((string) ($p['brand_name'] ?? '')) ?> · <?= e((string) ($p['category_name'] ?? '')) ?></div>
                                <div class="mt-3 flex items-end justify-between">
                                    <div>
                                        <div class="text-[10px] text-slate-400 line-through"><?= e(money($price['base_list_price'], $curSymbol($price['currency']))) ?></div>
                                        <div class="font-mono text-lg font-bold text-slate-900"><?= e(money($price['unit_net'])) ?></div>
                                        <div class="text-[10px] text-slate-500">KDV Dahil: <?= e(money($price['unit_gross'])) ?></div>
                                    </div>
                                </div>
                                <form action="<?= url('portal/sepet/ekle') ?>" method="POST" class="mt-3 flex items-center gap-1.5">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                                    <input type="number" name="quantity" value="<?= (int) $p['min_order_qty'] ?>" min="<?= (int) $p['min_order_qty'] ?>" step="<?= (int) $p['order_step_qty'] ?>" aria-label="Adet" <?= $oos ? 'disabled' : '' ?> class="w-20 rounded-lg border border-slate-300 px-2 py-2 text-center text-sm tabular-nums disabled:bg-slate-100">
                                    <button type="submit" aria-label="Sepete Ekle: <?= e((string) $p['sku']) ?>" <?= $oos ? 'disabled' : '' ?> class="flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold text-white <?= $oos ? 'cursor-not-allowed bg-slate-300' : 'bg-action hover:bg-blue-700' ?>"><i data-lucide="shopping-cart" class="h-4 w-4"></i> <?= $oos ? 'Stok Yok' : 'Sepete Ekle' ?></button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <nav class="mt-5 flex flex-wrap items-center justify-center gap-1.5">
                    <a href="<?= e($buildUrl(['sayfa' => max(1, $page - 1)])) ?>" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 <?= $page <= 1 ? 'pointer-events-none opacity-40' : '' ?>">‹ Önceki</a>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i === 1 || $i === $totalPages || abs($i - $page) <= 1): ?>
                            <a href="<?= e($buildUrl(['sayfa' => $i])) ?>" class="rounded-lg border px-3 py-1.5 text-sm font-semibold <?= $i === $page ? 'border-action bg-action text-white' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' ?>"><?= $i ?></a>
                        <?php elseif (abs($i - $page) === 2): ?>
                            <span class="px-1 text-slate-400">…</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <a href="<?= e($buildUrl(['sayfa' => min($totalPages, $page + 1)])) ?>" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50 <?= $page >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">Sonraki ›</a>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</div>
