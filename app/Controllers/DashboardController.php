<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

/**
 * Class DashboardController
 *
 * B2B Bayi Portalı Ana Kontrol Kokpiti.
 * Cari bakiye, kullanılabilir risk limiti, onay bekleyen siparişler,
 * aylık alım grafikleri ve son sevkiyat bildirimlerini sunar.
 *
 * @package App\Controllers
 */
class DashboardController extends PortalController
{
    public function index(Request $request): Response
    {
        $companyId = $this->companyId();
        $credit = $this->credit();

        $pendingApprovals = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM orders WHERE company_id = :id AND status IN ('pending_company_approval','pending_admin_approval')",
            [':id' => $companyId]
        );

        $monthlyPurchases = (float) Database::fetchColumn(
            "SELECT COALESCE(SUM(grand_total), 0) FROM orders
             WHERE company_id = :id AND status NOT IN ('cancelled','rejected')
               AND DATE_FORMAT(created_at, '%Y-%m') = :ym",
            [':id' => $companyId, ':ym' => date('Y-m')]
        );

        $totalOrders = (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM orders WHERE company_id = :id',
            [':id' => $companyId]
        );

        // 6 aylık alım grafiği
        $monthShort = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
        $from = date('Y-m-01', strtotime('-5 months'));
        $rows = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(grand_total), 0) AS total
             FROM orders
             WHERE company_id = :id AND status NOT IN ('cancelled','rejected') AND created_at >= :from
             GROUP BY ym",
            [':id' => $companyId, ':from' => $from]
        );
        $byMonth = [];
        foreach ($rows as $r) {
            $byMonth[$r['ym']] = (float) $r['total'];
        }
        $chartLabels = [];
        $chartValues = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts = strtotime('-' . $i . ' months');
            $key = date('Y-m', $ts);
            $chartLabels[] = $monthShort[(int) date('n', $ts) - 1] . ' ' . date('y', $ts);
            $chartValues[] = $byMonth[$key] ?? 0.0;
        }

        $recentOrders = Database::fetchAll(
            "SELECT o.id, o.order_number, o.status, o.grand_total, o.created_at,
                    (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
             FROM orders o
             WHERE o.company_id = :id
             ORDER BY o.created_at DESC
             LIMIT 6",
            [':id' => $companyId]
        );

        $featured = Database::fetchAll(
            'SELECT p.id, p.sku, p.name, p.slug, p.main_image, p.base_list_price, p.currency, p.unit_type,
                    COALESCE(SUM(s.physical_qty - s.reserved_qty), 0) AS stock
             FROM products p
             LEFT JOIN product_stocks s ON s.product_id = p.id
             WHERE p.is_active = 1 AND p.is_featured = 1
             GROUP BY p.id
             ORDER BY p.id
             LIMIT 4'
        );

        $usedLimit = $credit['balance'] + $credit['open_orders'];
        $usagePercent = $credit['limit'] > 0 ? min(100, round(($usedLimit / $credit['limit']) * 100, 1)) : 0;

        return $this->renderPortal('dashboard', [
            'title'            => 'Kokpit',
            'credit'           => $credit,
            'pendingApprovals' => $pendingApprovals,
            'monthlyPurchases' => $monthlyPurchases,
            'totalOrders'      => $totalOrders,
            'chartLabels'      => $chartLabels,
            'chartValues'      => $chartValues,
            'recentOrders'     => $recentOrders,
            'featured'         => $featured,
            'usedLimit'        => $usedLimit,
            'usagePercent'     => $usagePercent,
        ]);
    }
}
