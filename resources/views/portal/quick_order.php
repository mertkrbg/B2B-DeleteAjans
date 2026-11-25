<?php $rowCount = (int) ($rowCount ?? 15); ?>
<div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">Hızlı Sipariş</h1>
        <p class="mt-1 text-sm text-slate-500">SKU/barkod ile satır satır sipariş verin veya toplu yapıştırma / CSV yükleyin.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="<?= url('portal/hizli-siparis/sablon') ?>" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i data-lucide="download" class="h-4 w-4"></i> CSV Şablonu</a>
        <a href="<?= url('portal/sepet') ?>" class="inline-flex items-center gap-2 rounded-xl bg-action px-3 py-2 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="shopping-cart" class="h-4 w-4"></i> Sepete Git (<?= (int) ($cartSummary['count'] ?? 0) ?>)</a>
    </div>
</div>

<?php if (!empty($bulkResult)): ?>
    <div class="mb-4 rounded-2xl border <?= $bulkResult['added'] > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' ?> p-4">
        <div class="flex items-center gap-2 text-sm font-semibold <?= $bulkResult['added'] > 0 ? 'text-emerald-800' : 'text-amber-800' ?>">
            <i data-lucide="clipboard-check" class="h-4 w-4"></i>
            Toplu Yükleme Sonucu: <?= (int) $bulkResult['added'] ?> ürün sepete eklendi, <?= count($bulkResult['errors']) ?> hatalı satır.
        </div>
        <?php if (!empty($bulkResult['valid'])): ?>
            <div class="mt-3 overflow-x-auto rounded-xl border border-emerald-200 bg-white">
                <table class="w-full min-w-[420px] text-left text-xs">
                    <thead class="bg-emerald-50 text-emerald-700"><tr><th class="px-3 py-2 font-semibold">SKU</th><th class="px-3 py-2 font-semibold">Ürün</th><th class="px-3 py-2 text-right font-semibold">Adet</th><th class="px-3 py-2 text-right font-semibold">Tutar</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($bulkResult['valid'] as $vr): ?>
                            <tr><td class="px-3 py-1.5 font-mono"><?= e((string) $vr['sku']) ?></td><td class="px-3 py-1.5"><?= e((string) $vr['name']) ?></td><td class="px-3 py-1.5 text-right tabular-nums"><?= (int) $vr['quantity'] ?></td><td class="px-3 py-1.5 text-right font-mono"><?= e(money($vr['line_total'])) ?></td></tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        <?php if (!empty($bulkResult['errors'])): ?>
            <ul class="mt-3 space-y-1 rounded-xl border border-red-200 bg-white p-3 text-xs text-red-700">
                <?php foreach ($bulkResult['errors'] as $err): ?><li class="flex items-start gap-1.5"><i data-lucide="alert-circle" class="mt-0.5 h-3.5 w-3.5 shrink-0"></i><?= e((string) $err) ?></li><?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
    <div class="xl:col-span-2 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <form action="<?= url('portal/hizli-siparis/aktar') ?>" method="POST" id="quickOrderForm">
            <?= csrf_field() ?>
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                <h2 class="text-sm font-bold text-slate-800">Sipariş Matrisi</h2>
                <span class="text-[11px] text-slate-400">SKU yazın, listeden seçin · Enter ile alt satır</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-[10px] uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="w-8 px-2 py-2 font-semibold">#</th>
                            <th class="px-3 py-2 font-semibold">SKU / Barkod / OEM</th>
                            <th class="px-3 py-2 font-semibold">Ürün</th>
                            <th class="px-3 py-2 text-right font-semibold">Stok</th>
                            <th class="px-3 py-2 text-right font-semibold">Birim Fiyat</th>
                            <th class="w-24 px-3 py-2 text-right font-semibold">Adet</th>
                            <th class="px-3 py-2 text-right font-semibold">Tutar</th>
                            <th class="w-8 px-2 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php for ($i = 1; $i <= $rowCount; $i++): ?>
                            <tr class="qo-row hover:bg-slate-50/50">
                                <td class="px-2 py-1.5 text-center text-[11px] text-slate-400"><?= $i ?></td>
                                <td class="relative px-2 py-1.5">
                                    <input type="text" class="qo-sku w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs focus:border-action focus:ring-1 focus:ring-action/20" placeholder="B2B-ELK-..." autocomplete="off">
                                    <input type="hidden" class="qo-product" name="product_id[]" value="">
                                    <div class="qo-suggest absolute left-2 right-2 top-full z-30 mt-1 hidden max-h-64 overflow-auto rounded-lg border border-slate-200 bg-white shadow-xl"></div>
                                </td>
                                <td class="qo-name px-3 py-1.5 text-xs text-slate-500">—</td>
                                <td class="qo-stock px-3 py-1.5 text-right text-xs text-slate-400">—</td>
                                <td class="qo-price px-3 py-1.5 text-right font-mono text-xs text-slate-700">—</td>
                                <td class="px-2 py-1.5">
                                    <input type="number" class="qo-qty w-full rounded-lg border border-slate-300 px-2 py-1.5 text-right text-xs tabular-nums disabled:bg-slate-50" name="quantity[]" value="" min="1" step="1" readonly aria-label="Adet">
                                </td>
                                <td class="qo-total px-3 py-1.5 text-right font-mono text-xs font-semibold text-slate-800">—</td>
                                <td class="px-2 py-1.5"><button type="button" class="qo-clear rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-red-500" aria-label="Satırı temizle"><i data-lucide="x" class="h-3.5 w-3.5"></i></button></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
            <div class="flex flex-col gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-slate-600">Ara Toplam (KDV Hariç): <span id="qoGrand" class="font-mono text-base font-bold text-slate-900">0,00 ₺</span></div>
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-action px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700"><i data-lucide="arrow-right" class="h-4 w-4"></i> Sepete Aktar</button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="text-sm font-bold text-slate-800">Toplu Yükleme</h2>
        <p class="mt-1 text-xs text-slate-500">Excel'den kopyalayıp yapıştırın veya CSV yükleyin. Format: <span class="font-mono">SKU;ADET</span></p>
        <form action="<?= url('portal/hizli-siparis/toplu') ?>" method="POST" enctype="multipart/form-data" class="mt-3 space-y-3">
            <?= csrf_field() ?>
            <textarea name="bulk_text" rows="6" class="w-full rounded-xl border border-slate-300 px-3 py-2 font-mono text-xs focus:border-action focus:ring-2 focus:ring-action/20" placeholder="B2B-ELK-1001;10&#10;B2B-LGT-2001;4&#10;B2B-HRD-4001;5"></textarea>
            <div>
                <label class="mb-1 block text-xs font-semibold text-slate-600">veya CSV / TXT dosyası</label>
                <input type="file" name="bulk_file" accept=".csv,.txt" class="block w-full text-xs text-slate-500 file:mr-2 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-700">
            </div>
            <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-xl bg-navy px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"><i data-lucide="upload" class="h-4 w-4"></i> Toplu Aktar</button>
        </form>
        <div class="mt-4 rounded-xl bg-slate-50 p-3 text-[11px] leading-relaxed text-slate-500">
            <p class="font-semibold text-slate-600">Kurallar</p>
            <p class="mt-1">· Minimum sipariş adedi ve sipariş katı (step) kuralları uygulanır.</p>
            <p>· Stok yetersizse veya SKU bulunamazsa satır hata olarak raporlanır.</p>
        </div>
    </div>
</div>

<script>
(function () {
    var searchUrl = <?= json_encode(url('portal/hizli-siparis/ara'), JSON_UNESCAPED_SLASHES) ?>;
    var priceUrl = <?= json_encode(url('portal/hizli-siparis/fiyat'), JSON_UNESCAPED_SLASHES) ?>;
    var rows = Array.prototype.slice.call(document.querySelectorAll('.qo-row'));
    var grandEl = document.getElementById('qoGrand');

    function fmt(n) {
        return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n || 0) + ' ₺';
    }

    function recalc() {
        var total = 0;
        rows.forEach(function (r) {
            var price = parseFloat(r.dataset.price || '0');
            var qtyEl = r.querySelector('.qo-qty');
            var qty = parseInt(qtyEl.value || '0', 10);
            var totalEl = r.querySelector('.qo-total');
            if (r.dataset.productId && price > 0 && qty > 0) {
                var line = price * qty;
                total += line;
                totalEl.textContent = fmt(line);
            } else {
                totalEl.textContent = '—';
            }
        });
        grandEl.textContent = fmt(total);
    }

    function resetRow(r) {
        delete r.dataset.productId;
        delete r.dataset.price;
        r.querySelector('.qo-product').value = '';
        var sku = r.querySelector('.qo-sku');
        sku.value = '';
        r.querySelector('.qo-name').textContent = '—';
        var stock = r.querySelector('.qo-stock');
        stock.textContent = '—';
        stock.className = 'qo-stock px-3 py-1.5 text-right text-xs text-slate-400';
        r.querySelector('.qo-price').textContent = '—';
        var qty = r.querySelector('.qo-qty');
        qty.value = '';
        qty.readOnly = true;
        qty.min = 1;
        qty.step = 1;
        r.querySelector('.qo-suggest').classList.add('hidden');
        recalc();
    }

    function selectProduct(r, product) {
        r.dataset.productId = product.id;
        r.dataset.price = product.price;
        r.querySelector('.qo-product').value = product.id;
        r.querySelector('.qo-sku').value = product.sku;
        r.querySelector('.qo-name').textContent = product.name;
        var stock = r.querySelector('.qo-stock');
        stock.textContent = product.stock > 0 ? product.stock + ' ' + product.unit : 'Stok Yok';
        stock.className = 'qo-stock px-3 py-1.5 text-right text-xs font-semibold ' + (product.stock > 0 ? 'text-emerald-600' : 'text-red-500');
        r.querySelector('.qo-price').textContent = product.price_formatted;
        var qty = r.querySelector('.qo-qty');
        qty.readOnly = false;
        qty.min = product.min;
        qty.step = product.step;
        qty.value = product.min;
        r.querySelector('.qo-suggest').classList.add('hidden');
        recalc();
    }

    function renderSuggestions(r, products) {
        var box = r.querySelector('.qo-suggest');
        box.innerHTML = '';
        if (!products.length) {
            var empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-xs text-slate-400';
            empty.textContent = 'Sonuç bulunamadı';
            box.appendChild(empty);
            box.classList.remove('hidden');
            return;
        }
        products.forEach(function (p) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'flex w-full items-center justify-between gap-2 border-b border-slate-50 px-3 py-2 text-left hover:bg-slate-50';
            btn.innerHTML = '<span><span class="block font-mono text-[11px] text-slate-500"></span><span class="block text-xs font-medium text-slate-800"></span></span><span class="shrink-0 font-mono text-[11px] font-semibold text-action"></span>';
            btn.children[0].children[0].textContent = p.sku;
            btn.children[0].children[1].textContent = p.name;
            btn.children[1].textContent = p.price_formatted;
            btn.addEventListener('click', function () { selectProduct(r, p); });
            box.appendChild(btn);
        });
        box.classList.remove('hidden');
    }

    rows.forEach(function (r) {
        var skuInput = r.querySelector('.qo-sku');
        var box = r.querySelector('.qo-suggest');
        var timer = null;

        skuInput.addEventListener('input', function () {
            clearTimeout(timer);
            var v = skuInput.value.trim();
            if (v.length < 2) { box.classList.add('hidden'); return; }
            timer = setTimeout(function () {
                fetch(searchUrl + '?q=' + encodeURIComponent(v), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) { renderSuggestions(r, data.products || []); })
                    .catch(function () { box.classList.add('hidden'); });
            }, 220);
        });

        skuInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                var first = box.querySelector('button');
                if (first) { first.click(); }
                var next = r.nextElementSibling;
                if (next && next.classList.contains('qo-row')) {
                    next.querySelector('.qo-sku').focus();
                }
            }
        });

        r.querySelector('.qo-qty').addEventListener('input', function () {
            var qty = r.querySelector('.qo-qty');
            var min = parseInt(qty.min || '1', 10);
            if (qty.value !== '' && parseInt(qty.value, 10) < min) { qty.value = min; }
            recalc();
            if (!r.dataset.productId) { return; }
            clearTimeout(r._priceTimer);
            r._priceTimer = setTimeout(function () {
                var q = parseInt(qty.value || '0', 10);
                if (q <= 0) { return; }
                fetch(priceUrl + '?product_id=' + encodeURIComponent(r.dataset.productId) + '&quantity=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data && data.ok) {
                            r.dataset.price = data.unit_net;
                            r.querySelector('.qo-price').textContent = data.price_formatted;
                            recalc();
                        }
                    })
                    .catch(function () {});
            }, 250);
        });

        r.querySelector('.qo-clear').addEventListener('click', function () { resetRow(r); });
    });

    document.addEventListener('click', function (e) {
        rows.forEach(function (r) {
            if (!r.contains(e.target)) { r.querySelector('.qo-suggest').classList.add('hidden'); }
        });
    });
})();
</script>
