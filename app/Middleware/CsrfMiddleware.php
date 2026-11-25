<?php declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class CsrfMiddleware
 *
 * Çapraz İstek Sahteciliği (CSRF) Güvenlik Filtresi.
 * POST, PUT, PATCH, DELETE metotlarında oturum CSRF token doğrulaması yapar.
 *
 * @package App\Middleware
 */
class CsrfMiddleware
{
    public function handle(Request $request, callable $next): Response
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if (!Session::validateCsrf($request->token())) {
                if ($request->isAjax() || str_contains(strtolower($request->input('HTTP_ACCEPT', '') ?? ''), 'json')) {
                    return Response::json(['success' => false, 'message' => 'Güvenlik doğrulaması başarısız. Sayfayı yenileyin.'], 419);
                }
                Session::flash('error', 'Güvenlik doğrulaması başarısız oldu. Lütfen formu yeniden gönderin.');
                $referer = $_SERVER['HTTP_REFERER'] ?? url('login');
                return Response::redirect($referer);
            }
        }
        return $next($request);
    }
}
