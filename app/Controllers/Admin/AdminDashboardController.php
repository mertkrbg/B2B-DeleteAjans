<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Services\CreditRiskService;

/**
 * Class AdminDashboardController
 *
 * ERP Yönetim Paneli Ana Kokpiti.
 * Toplam ciro, sipariş hacmi, aktif/onay bekleyen bayi sayıları,
 * riskli bayiler ve kritik stok uyarılarını özetler.
 *
 * @package App\Controllers\Admin
 */
class AdminDashboardController extends AdminController
{
    public function index(Request $request): Response
    {
        $totalRevenue = (float) Database::fetchColumn(
            "SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE status NOT IN ('cancelled','rejected')"
        );
        $orderCount = (int) Database::fetchColumn('SELECT COUNT(*) FROM orders');
        $pendingOrders = (int) Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE status IN ('pending_company_approval','pending_admin_approval')");
        $pendingCompanies = (int) Database::fetchColumn("SELECT COUNT(*) FROM companies WHERE status = 'pending'");
        $activeCompanies = (int) Database::fetchColumn("SELECT COUNT(*) FROM companies WHERE status = 'active'");

        $riskCompanies = 0;
        foreach (Database::fetchAll('SELECT id FROM companies WHERE status IN (\'active\',\'suspended\')') as $c) {
            $snap = CreditRiskService::snapshot((int) $c['id']);
            if ($snap['has_overdue'] || $snap['over_limit']) {
                $riskCompanies++;
            }
        }

        $monthShort = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
        $from = date('Y-m-01', strtotime('-5 months'));
        $rows = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(grand_total),0) AS total
             FROM orders WHERE status NOT IN ('cancelled','rejected') AND created_at >= :from GROUP BY ym",
            [':from' => $from]
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
            "SELECT o.id, o.order_number, o.status, o.grand_total, o.created_at, c.title AS company_title
             FROM orders o JOIN companies c ON c.id = o.company_id
             ORDER BY o.created_at DESC, o.id DESC LIMIT 8"
        );

        $pendingList = Database::fetchAll(
            "SELECT id, company_code, title, city, created_at, tax_plate_file FROM companies WHERE status = 'pending' ORDER BY created_at ASC LIMIT 6"
        );

        $topCompanies = Database::fetchAll(
            "SELECT c.id, c.title, COALESCE(SUM(o.grand_total),0) AS revenue, COUNT(o.id) AS orders
             FROM companies c LEFT JOIN orders o ON o.company_id = c.id AND o.status NOT IN ('cancelled','rejected')
             GROUP BY c.id ORDER BY revenue DESC LIMIT 5"
        );

        return $this->renderAdmin('dashboard', [
            'title' => 'Yönetim Kokpiti',
            'kpi' => [
                'totalRevenue' => $totalRevenue,
                'orderCount' => $orderCount,
                'pendingOrders' => $pendingOrders,
                'pendingCompanies' => $pendingCompanies,
                'activeCompanies' => $activeCompanies,
                'riskCompanies' => $riskCompanies,
            ],
            'chartLabels' => $chartLabels,
            'chartValues' => $chartValues,
            'recentOrders' => $recentOrders,
            'pendingList' => $pendingList,
            'topCompanies' => $topCompanies,
        ]);
    }
}
