<?php declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class B2BApprovedMiddleware
 *
 * Bayi hesap onay durumu denetleyicisi.
 * Firmanın durumunu ('active', 'pending', 'suspended') denetler.
 * Onay bekleyen veya askıya alınan bayileri bilgilendirme sayfasına yönlendirir.
 *
 * @package App\Middleware
 */
class B2BApprovedMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $role = (string) Session::get('role', '');
        if (in_array($role, ['super_admin', 'sales_manager', 'finance_admin'], true)) {
            return Response::redirect(url('admin'));
        }
        $companyId = Session::get('company_id');
        if ($companyId === null) {
            return Response::redirect(url('login'));
        }

        $company = Database::fetch(
            'SELECT id, title, status, tier_level FROM companies WHERE id = :id LIMIT 1',
            [':id' => $companyId]
        );

        if ($company === null) {
            Session::logout();
            session_start();
            Session::flash('error', 'Firma kaydınız bulunamadı. Lütfen tekrar giriş yapın.');
            return Response::redirect(url('login'));
        }

        if ($company['status'] !== 'active') {
            $html = \App\Core\View::render('portal.pending', [
                'title'   => 'Hesap Durumu',
                'company' => $company,
                'status'  => (string) $company['status'],
            ], 'auth');
            return Response::html($html, 403);
        }

        return $next($request);
    }
}
