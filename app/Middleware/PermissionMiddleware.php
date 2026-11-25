<?php declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class PermissionMiddleware
 *
 * Granüler RBAC (Rol Tabanlı Erişim Kontrolü) Filtresi.
 * İstenen spesifik yetkiyi (örn: order.create, team.manage) kullanıcı rolüyle kıyaslar.
 *
 * @package App\Middleware
 */
class PermissionMiddleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function handle(Request $request, callable $next): Response
    {
        $role = (string) Session::get('role', '');
        $matrix = (array) config('permissions', []);
        $permissions = $matrix[$role] ?? [];

        if (!in_array($this->permission, $permissions, true)) {
            if ($request->isAjax()) {
                return Response::json(['success' => false, 'message' => 'Bu işlem için yetkiniz bulunmuyor.'], 403);
            }
            Session::flash('error', 'Bu sayfaya erişim yetkiniz bulunmuyor.');
            return Response::redirect(url('portal'));
        }
        return $next($request);
    }
}
