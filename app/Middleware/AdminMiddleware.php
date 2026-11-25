<?php declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class AdminMiddleware
 *
 * Yönetim paneli (ERP/Admin) erişim filtresi.
 * Kullanıcının 'admin.panel' yetkisine sahip olup olmadığını denetler.
 *
 * @package App\Middleware
 */
class AdminMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        $role = (string) Session::get('role', '');
        $matrix = (array) config('permissions', []);
        $permissions = $matrix[$role] ?? [];

        if (!in_array('admin.panel', $permissions, true)) {
            Session::flash('error', 'Yönetim paneli için yetkiniz bulunmuyor.');
            return Response::redirect(url('portal'));
        }
        return $next($request);
    }
}
