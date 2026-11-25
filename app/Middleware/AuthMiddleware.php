<?php declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class AuthMiddleware
 *
 * Kullanıcı kimlik doğrulama kontrolü.
 * Giriş yapmamış kullanıcıları /login sayfasına veya 401 JSON yanıtına yönlendirir.
 *
 * @package App\Middleware
 */
class AuthMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (!Session::isAuthenticated()) {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'Oturum süreniz doldu. Lütfen tekrar giriş yapın.'], 401);
            }
            Session::flash('warning', 'Bu sayfaya erişmek için giriş yapmalısınız.');
            return Response::redirect(url('login'));
        }
        return $next($request);
    }
}
