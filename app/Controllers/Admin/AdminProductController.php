<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Class AdminProductController
 *
 * Ürün ve Çoklu Depo Stok Yönetimi Kontrolcüsü (ERP).
 * Ürün ekleme/düzenleme, kademeli iskonto tanımlama, ana depo, sevkiyat deposu
 * ve yedek depo bazında fiziksel stok girişlerini koordine eder.
 *
 * @package App\Controllers\Admin
 */
class AdminProductController extends AdminController
{
    public function index(Request $request): Response
    {
        $q = trim((string) $request->query('q', ''));
        $where = '1=1';
        $params = [];
        if ($q !== '') {
            $where .= ' AND (p.sku LIKE :q1 OR p.name LIKE :q2)';
            $params[':q1'] = '%' . $q . '%';
            $params[':q2'] = '%' . $q . '%';
        }
        $products = Database::fetchAll(
            "SELECT p.*, c.name AS category_name, b.name AS brand_name,
                    (SELECT COALESCE(SUM(ps.physical_qty - ps.reserved_qty),0) FROM product_stocks ps WHERE ps.product_id = p.id) AS stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE $where ORDER BY p.id DESC LIMIT 200",
            $params
        );
        return $this->renderAdmin('products', [
            'title' => 'Ürünler & Stok',
            'products' => $products,
            'q' => $q,
        ]);
    }

    private function formData(): array
    {
        return [
            'categories' => Database::fetchAll('SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order, name'),
            'brands' => Database::fetchAll('SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name'),
            'warehouses' => Database::fetchAll('SELECT id, code, name FROM warehouses WHERE is_active = 1 ORDER BY priority_order'),
        ];
    }

    public function create(Request $request): Response
    {
        return $this->renderAdmin('product_edit', array_merge($this->formData(), [
            'title' => 'Yeni Ürün',
            'product' => null,
            'stocks' => [],
            'tiers' => [],
        ]));
    }

    public function store(Request $request): Response
    {
        $data = $this->validateAndCollect($request, null);
        if (isset($data['error'])) {
            Session::flash('error', $data['error']);
            return Response::redirect(url('admin/urunler/yeni'));
        }
        $slug = $this->slugify($data['name']) . '-' . $this->slugify($data['sku']);
        if (mb_strlen($slug) > 250) {
            $slug = mb_substr($slug, 0, 250);
        }
        $exists = Database::fetchColumn('SELECT COUNT(*) FROM products WHERE slug = :s', [':s' => $slug]);
        if ((int) $exists > 0) {
            $slug .= '-' . time();
        }
        $productId = Database::insert('products', [
            'category_id' => $data['category_id'], 'brand_id' => $data['brand_id'],
            'sku' => $data['sku'], 'barcode' => $data['barcode'], 'oem_code' => $data['oem_code'],
            'name' => $data['name'], 'slug' => $slug, 'short_description' => $data['short_description'],
            'technical_specs_json' => null, 'unit_type' => $data['unit_type'],
            'box_quantity' => $data['box_quantity'], 'min_order_qty' => $data['min_order_qty'],
            'order_step_qty' => $data['order_step_qty'], 'base_list_price' => $data['base_list_price'],
            'currency' => $data['currency'], 'vat_rate' => $data['vat_rate'], 'desi' => $data['desi'],
            'main_image' => 'images/products/default.png', 'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'], 'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->saveStocks($productId, $request);
        $this->saveTiers($productId, $request);
        Session::flash('success', 'Ürün oluşturuldu.');
        return Response::redirect(url('admin/urunler/' . $productId));
    }

    public function edit(Request $request, string $id): Response
    {
        $product = Database::fetch('SELECT * FROM products WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($product === null) {
            return Response::html(View::render('errors.404', ['title' => 'Ürün Bulunamadı'], 'auth'), 404);
        }
        $stocks = [];
        foreach (Database::fetchAll('SELECT * FROM product_stocks WHERE product_id = :id', [':id' => $product['id']]) as $s) {
            $stocks[(int) $s['warehouse_id']] = $s;
        }
        $tiers = Database::fetchAll('SELECT * FROM product_tier_prices WHERE product_id = :id ORDER BY min_qty', [':id' => $product['id']]);
        return $this->renderAdmin('product_edit', array_merge($this->formData(), [
            'title' => 'Ürün: ' . $product['name'],
            'product' => $product,
            'stocks' => $stocks,
            'tiers' => $tiers,
        ]));
    }

    public function update(Request $request, string $id): Response
    {
        $product = Database::fetch('SELECT * FROM products WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($product === null) {
            Session::flash('error', 'Ürün bulunamadı.');
            return Response::redirect(url('admin/urunler'));
        }
        $data = $this->validateAndCollect($request, (int) $product['id']);
        if (isset($data['error'])) {
            Session::flash('error', $data['error']);
            return Response::redirect(url('admin/urunler/' . $product['id']));
        }
        Database::update('products', [
            'category_id' => $data['category_id'], 'brand_id' => $data['brand_id'],
            'sku' => $data['sku'], 'barcode' => $data['barcode'], 'oem_code' => $data['oem_code'],
            'name' => $data['name'], 'short_description' => $data['short_description'],
            'unit_type' => $data['unit_type'], 'box_quantity' => $data['box_quantity'],
            'min_order_qty' => $data['min_order_qty'], 'order_step_qty' => $data['order_step_qty'],
            'base_list_price' => $data['base_list_price'], 'currency' => $data['currency'],
            'vat_rate' => $data['vat_rate'], 'desi' => $data['desi'],
            'is_featured' => $data['is_featured'], 'is_active' => $data['is_active'],
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $product['id']]);
        $this->saveStocks((int) $product['id'], $request);
        $this->saveTiers((int) $product['id'], $request);
        Session::flash('success', 'Ürün güncellendi.');
        return Response::redirect(url('admin/urunler/' . $product['id']));
    }

    private function validateAndCollect(Request $request, ?int $id): array
    {
        $name = $request->string('name');
        $sku = strtoupper($request->string('sku'));
        if ($name === '' || $sku === '') {
            return ['error' => 'Ürün adı ve stok kodu zorunludur.'];
        }
        $exists = (int) Database::fetchColumn('SELECT COUNT(*) FROM products WHERE sku = :s AND id <> :id', [':s' => $sku, ':id' => $id ?? 0]);
        if ($exists > 0) {
            return ['error' => 'Bu stok kodu zaten kullanılıyor.'];
        }
        $categoryId = (int) $request->input('category_id', 0);
        if ($categoryId <= 0 || Database::fetchColumn('SELECT COUNT(*) FROM categories WHERE id = :id', [':id' => $categoryId]) == 0) {
            return ['error' => 'Geçerli bir kategori seçiniz.'];
        }
        $price = (float) $request->input('base_list_price', 0);
        if ($price <= 0) {
            return ['error' => 'Liste fiyatı sıfırdan büyük olmalıdır.'];
        }
        $currency = in_array($request->string('currency'), ['TRY', 'USD', 'EUR'], true) ? $request->string('currency') : 'TRY';
        $unitType = in_array($request->string('unit_type'), ['adet', 'kutu', 'koli', 'metre', 'kg', 'set'], true) ? $request->string('unit_type') : 'adet';
        $brandId = $request->int('brand_id');
        return [
            'name' => $name,
            'sku' => $sku,
            'barcode' => $request->string('barcode') !== '' ? $request->string('barcode') : null,
            'oem_code' => $request->string('oem_code') !== '' ? $request->string('oem_code') : null,
            'category_id' => $categoryId,
            'brand_id' => $brandId > 0 ? $brandId : null,
            'base_list_price' => $price,
            'currency' => $currency,
            'vat_rate' => (float) $request->input('vat_rate', 20),
            'unit_type' => $unitType,
            'box_quantity' => max(1, (int) $request->input('box_quantity', 1)),
            'min_order_qty' => max(1, (int) $request->input('min_order_qty', 1)),
            'order_step_qty' => max(1, (int) $request->input('order_step_qty', 1)),
            'desi' => max(0.01, (float) $request->input('desi', 1)),
            'short_description' => $request->string('short_description'),
            'is_featured' => $request->bool('is_featured') ? 1 : 0,
            'is_active' => $request->bool('is_active') ? 1 : 0,
        ];
    }

    private function saveStocks(int $productId, Request $request): void
    {
        $stocks = (array) $request->input('stock', []);
        foreach ($stocks as $warehouseId => $qty) {
            $warehouseId = (int) $warehouseId;
            $physical = max(0, (int) $qty);
            $exists = Database::fetch('SELECT id FROM product_stocks WHERE product_id = :p AND warehouse_id = :w', [':p' => $productId, ':w' => $warehouseId]);
            if ($exists !== null) {
                Database::update('product_stocks', ['physical_qty' => $physical], 'id = :id', [':id' => $exists['id']]);
            } else {
                Database::insert('product_stocks', ['product_id' => $productId, 'warehouse_id' => $warehouseId, 'physical_qty' => $physical, 'reserved_qty' => 0, 'incoming_qty' => 0]);
            }
        }
    }

    private function saveTiers(int $productId, Request $request): void
    {
        $mins = (array) $request->input('tier_min', []);
        $maxs = (array) $request->input('tier_max', []);
        $rates = (array) $request->input('tier_rate', []);
        Database::delete('product_tier_prices', 'product_id = :p', [':p' => $productId]);
        foreach ($mins as $idx => $min) {
            $min = (int) $min;
            if ($min <= 0) {
                continue;
            }
            $max = isset($maxs[$idx]) && $maxs[$idx] !== '' ? (int) $maxs[$idx] : null;
            Database::insert('product_tier_prices', [
                'product_id' => $productId, 'min_qty' => $min, 'max_qty' => $max,
                'discount_rate' => (float) ($rates[$idx] ?? 0),
            ]);
        }
    }

    private function slugify(string $text): string
    {
        $text = strtr($text, ['ç'=>'c','ğ'=>'g','ı'=>'i','ö'=>'o','ş'=>'s','ü'=>'u','Ç'=>'c','Ğ'=>'g','İ'=>'i','Ö'=>'o','Ş'=>'s','Ü'=>'u']);
        $text = strtolower($text);
        return trim(preg_replace('/[^a-z0-9]+/', '-', $text) ?? '', '-');
    }
}
