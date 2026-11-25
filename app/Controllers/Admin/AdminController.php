<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

/**
 * Class AdminController
 *
 * ERP / Yönetim Paneli Temel Soyut Kontrolcüsü (Base Admin Controller).
 * Bekleyen bayi başvuruları, siparişler, teklifler ve ödeme bildirimleri sayaçlarını
 * tüm admin görünümlerine ortak olarak enjekte eder.
 *
 * @package App\Controllers\Admin
 */
abstract class AdminController
{
    protected function renderAdmin(string $template, array $data): Response
    {
        $counts = [
            'pendingCompanies' => (int) Database::fetchColumn("SELECT COUNT(*) FROM companies WHERE status = 'pending'"),
            'pendingOrders'    => (int) Database::fetchColumn("SELECT COUNT(*) FROM orders WHERE status IN ('pending_company_approval','pending_admin_approval')"),
            'pendingQuotes'    => (int) Database::fetchColumn("SELECT COUNT(*) FROM quotes WHERE status IN ('requested','reviewing')"),
            'pendingPayments'  => (int) Database::fetchColumn("SELECT COUNT(*) FROM payment_notifications WHERE status = 'pending'"),
        ];
        $common = [
            'counts'      => $counts,
            'authUser'    => Session::auth(),
            'currentPath' => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            'pageTitle'   => $data['title'] ?? 'Yönetim',
        ];
        $html = View::render('admin.' . $template, array_merge($common, $data), 'admin');
        return Response::html($html);
    }
}
