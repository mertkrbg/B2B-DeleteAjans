<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\PricingEngine;

/**
 * Class CatalogController
 *
 * B2B Ürün Kataloğu ve Detay Kontrolcüsü.
 * Filtreleme (kategori, marka, arama terimi, stok durumu), sayfalandırma,
 * bayi bazlı net fiyatlandırma ve çoklu depo stok durumunu görüntüler.
 *
 * @package App\Controllers
 */
class CatalogController extends PortalController
{
    public function index(Request $request): Response
    {
        $perPage = 20;
        $page = max(1, (int) $request->query('sayfa', 1));
        $offset = ($page - 1) * $perPage;

        $q = trim((string) $request->query('q', ''));
        $categorySlug = trim((string) $request->query('kategori', ''));
        $brandId = (int) $request->query('marka', 0);
        $inStock = (string) $request->query('stok', '') === '1';
        $view = (string) $request->query('gorunum', 'card') === 'tablo' ? 'table' : 'card';
        $sort = (string) $request->query('sirala', 'featured');

        $where = ['p.is_active = 1'];
        $params = [];

        if ($q !== '') {
            $where[] = '(p.name LIKE :q_name OR p.sku LIKE :q_sku OR p.barcode LIKE :q_barcode OR p.oem_code LIKE :q_oem)';
            foreach ([':q_name', ':q_sku', ':q_barcode', ':q_oem'] as $qKey) {
                $params[$qKey] = '%' . $q . '%';
            }
        }

        $selectedCategory = null;
        if ($categorySlug !== '') {
            $selectedCategory = Database::fetch('SELECT id, name, slug FROM categories WHERE slug = :s LIMIT 1', [':s' => $categorySlug]);
            if ($selectedCategory !== null) {
                $ids = [(int) $selectedCategory['id']];
                foreach (Database::fetchAll('SELECT id FROM categories WHERE parent_id = :p', [':p' => $selectedCategory['id']]) as $ch) {
                    $ids[] = (int) $ch['id'];
                }
                $keys = [];
                foreach ($ids as $i => $id) {
                    $key = ':cat' . $i;
                    $keys[] = $key;
                    $params[$key] = $id;
                }
                $where[] = 'p.category_id IN (' . implode(',', $keys) . ')';
            }
        }

        if ($brandId > 0) {
            $where[] = 'p.brand_id = :brand';
            $params[':brand'] = $brandId;
        }

        $stockExpr = '(SELECT COALESCE(SUM(ps.physical_qty - ps.reserved_qty), 0) FROM product_stocks ps WHERE ps.product_id = p.id)';
        if ($inStock) {
            $where[] = $stockExpr . ' > 0';
        }

        $whereSql = implode(' AND ', $where);

        $companyRow = $this->company();
        $plid = isset($companyRow['price_list_id']) && $companyRow['price_list_id'] !== null ? (int) $companyRow['price_list_id'] : null;

        $joins = 'LEFT JOIN currency_rates cr ON cr.currency_code = p.currency';
        $listParams = $params;
        if ($plid !== null) {
            $joins .= ' LEFT JOIN price_list_items pli ON pli.product_id = p.id AND pli.price_list_id = :plid_sort';
            $listParams[':plid_sort'] = $plid;
            $priceExpr = 'COALESCE(pli.special_price, p.base_list_price) * COALESCE(cr.selling_rate, 1)';
        } else {
            $priceExpr = 'p.base_list_price * COALESCE(cr.selling_rate, 1)';
        }

        $orderSql = match ($sort) {
            'price_asc'  => $priceExpr . ' ASC, p.id ASC',
            'price_desc' => $priceExpr . ' DESC, p.id ASC',
            'newest'     => 'p.id DESC',
            'name'       => 'p.name ASC',
            default      => 'p.is_featured DESC, p.id ASC',
        };

        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM products p WHERE $whereSql", $params);

        $products = Database::fetchAll(
            "SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name, $stockExpr AS stock
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             $joins
             WHERE $whereSql
             ORDER BY $orderSql
             LIMIT $perPage OFFSET $offset",
            $listParams
        );

        $engine = PricingEngine::forCompany($this->companyId());
        $prices = $engine->priceMany($products);
        foreach ($products as &$product) {
            $product['price'] = $prices[(int) $product['id']];
        }
        unset($product);

        $categoriesRaw = Database::fetchAll('SELECT id, parent_id, name, slug, icon FROM categories WHERE is_active = 1 ORDER BY sort_order, name');
        $categories = [];
        foreach ($categoriesRaw as $cat) {
            if ($cat['parent_id'] === null) {
                $cat['children'] = [];
                $categories[(int) $cat['id']] = $cat;
            }
        }
        foreach ($categoriesRaw as $cat) {
            if ($cat['parent_id'] !== null && isset($categories[(int) $cat['parent_id']])) {
                $categories[(int) $cat['parent_id']]['children'][] = $cat;
            }
        }

        $brands = Database::fetchAll('SELECT id, name FROM brands WHERE is_active = 1 ORDER BY name');

        $totalPages = max(1, (int) ceil($total / $perPage));
        $queryBase = array_filter([
            'q' => $q, 'kategori' => $categorySlug, 'marka' => $brandId > 0 ? $brandId : null,
            'stok' => $inStock ? '1' : null, 'gorunum' => $view, 'sirala' => $sort,
        ], static fn ($v) => $v !== null && $v !== '');

        return $this->renderPortal('catalog', [
            'title'          => 'Ürün Kataloğu',
            'products'       => $products,
            'categories'     => array_values($categories),
            'brands'         => $brands,
            'selectedCategory' => $selectedCategory,
            'filters'        => ['q' => $q, 'kategori' => $categorySlug, 'marka' => $brandId, 'stok' => $inStock, 'gorunum' => $view, 'sirala' => $sort],
            'total'          => $total,
            'page'           => $page,
            'totalPages'     => $totalPages,
            'queryBase'      => $queryBase,
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $product = Database::fetch(
            'SELECT p.*, c.name AS category_name, c.slug AS category_slug, b.name AS brand_name
             FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             LEFT JOIN brands b ON b.id = p.brand_id
             WHERE p.slug = :slug AND p.is_active = 1 LIMIT 1',
            [':slug' => $slug]
        );

        if ($product === null) {
            $html = \App\Core\View::render('errors.404', ['title' => 'Ürün Bulunamadı'], 'auth');
            return Response::html($html, 404);
        }

        $stocks = Database::fetchAll(
            'SELECT w.id, w.code, w.name, w.city, ps.physical_qty, ps.reserved_qty, ps.incoming_qty, ps.expected_date
             FROM product_stocks ps
             JOIN warehouses w ON w.id = ps.warehouse_id
             WHERE ps.product_id = :id AND w.is_active = 1
             ORDER BY w.priority_order',
            [':id' => $product['id']]
        );
        foreach ($stocks as &$stock) {
            $stock['available'] = max(0, (int) $stock['physical_qty'] - (int) $stock['reserved_qty']);
        }
        unset($stock);

        $specs = [];
        if (!empty($product['technical_specs_json'])) {
            $decoded = json_decode((string) $product['technical_specs_json'], true);
            $specs = is_array($decoded) ? $decoded : [];
        }

        $engine = PricingEngine::forCompany($this->companyId());
        $price = $engine->price($product, (int) $product['min_order_qty']);

        $related = Database::fetchAll(
            "SELECT p.*, (SELECT COALESCE(SUM(ps.physical_qty - ps.reserved_qty), 0) FROM product_stocks ps WHERE ps.product_id = p.id) AS stock
             FROM products p
             WHERE p.category_id = :cat AND p.id <> :id AND p.is_active = 1
             ORDER BY p.is_featured DESC LIMIT 4",
            [':cat' => $product['category_id'], ':id' => $product['id']]
        );
        if (!empty($related)) {
            $relatedPrices = $engine->priceMany($related);
            foreach ($related as &$rel) {
                $rel['price'] = $relatedPrices[(int) $rel['id']];
            }
            unset($rel);
        }

        return $this->renderPortal('product_detail', [
            'title'   => (string) $product['name'],
            'product' => $product,
            'stocks'  => $stocks,
            'specs'   => $specs,
            'price'   => $price,
            'related' => $related,
        ]);
    }
}
