<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;

/**
 * Class CompanyTeamController
 *
 * Bayi Alt Kullanıcı ve Ekip Yönetim Kontrolcüsü.
 * Firma sahibinin satın alma sorumlusu, satın almacı ve muhasebeci rolleri tanımlamasını,
 * alt kullanıcı sipariş limitleri belirlemesini ve durumlarını yönetmesini sağlar.
 *
 * @package App\Controllers
 */
class CompanyTeamController extends PortalController
{
    private const SUB_ROLES = ['purchasing_manager', 'buyer', 'accountant'];

    private function ownerGuard(): ?Response
    {
        if ((string) Session::get('role', '') !== 'company_owner') {
            Session::flash('error', 'Ekip yönetimi yalnızca firma sahibi tarafından yapılabilir.');
            return Response::redirect(url('portal'));
        }
        return null;
    }

    public function index(Request $request): Response
    {
        if ($response = $this->ownerGuard()) {
            return $response;
        }
        $users = Database::fetchAll(
            "SELECT * FROM users WHERE company_id = :co ORDER BY (role = 'company_owner') DESC, id ASC",
            [':co' => $this->companyId()]
        );
        return $this->renderPortal('team', [
            'title'     => 'Firma & Ekip Yönetimi',
            'teamUsers' => $users,
            'subRoles'  => self::SUB_ROLES,
        ]);
    }

    public function store(Request $request): Response
    {
        if ($response = $this->ownerGuard()) {
            return $response;
        }
        $role = $request->string('role');
        if (!in_array($role, self::SUB_ROLES, true)) {
            Session::flash('error', 'Geçersiz kullanıcı rolü.');
            return Response::redirect(url('portal/ekip'));
        }
        $data = [
            'full_name' => $request->string('full_name'),
            'email' => strtolower($request->string('email')),
            'phone' => $request->string('phone'),
            'password' => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ];
        $errors = Validator::validate($data, [
            'full_name' => 'required|min:3|max:150',
            'email' => 'required|email|max:180|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ], ['full_name' => 'Ad soyad', 'email' => 'E-posta', 'password' => 'Şifre']);
        if (!empty($errors)) {
            Validator::persistErrors($errors);
            old_input(['full_name' => $data['full_name'], 'email' => $data['email'], 'phone' => $data['phone']]);
            Session::flash('error', 'Lütfen formdaki hataları düzeltin.');
            return Response::redirect(url('portal/ekip'));
        }

        $limitRaw = $request->input('order_approval_limit');
        $limit = ($limitRaw !== null && $limitRaw !== '') ? (float) $limitRaw : null;

        Database::insert('users', [
            'company_id' => $this->companyId(),
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] !== '' ? $data['phone'] : null,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => $role,
            'order_approval_limit' => $limit,
            'can_view_prices' => $request->bool('can_view_prices') ? 1 : 0,
            'can_view_finance' => $request->bool('can_view_finance') ? 1 : 0,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        Session::forget('_errors');
        Session::forget('_old');
        Session::flash('success', 'Alt kullanıcı başarıyla eklendi.');
        return Response::redirect(url('portal/ekip'));
    }

    public function update(Request $request): Response
    {
        if ($response = $this->ownerGuard()) {
            return $response;
        }
        $userId = $request->int('user_id');
        $user = Database::fetch('SELECT * FROM users WHERE id = :id AND company_id = :co LIMIT 1', [':id' => $userId, ':co' => $this->companyId()]);
        if ($user === null) {
            Session::flash('error', 'Kullanıcı bulunamadı.');
            return Response::redirect(url('portal/ekip'));
        }
        if ($user['role'] === 'company_owner') {
            Session::flash('error', 'Firma sahibi hesabı bu ekrandan düzenlenemez.');
            return Response::redirect(url('portal/ekip'));
        }
        $role = $request->string('role');
        if (!in_array($role, self::SUB_ROLES, true)) {
            $role = (string) $user['role'];
        }
        $limitRaw = $request->input('order_approval_limit');
        $limit = ($limitRaw !== null && $limitRaw !== '') ? (float) $limitRaw : null;
        $fullName = $request->string('full_name');

        Database::update('users', [
            'full_name' => $fullName !== '' ? $fullName : (string) $user['full_name'],
            'phone' => $request->string('phone') !== '' ? $request->string('phone') : null,
            'role' => $role,
            'order_approval_limit' => $limit,
            'can_view_prices' => $request->bool('can_view_prices') ? 1 : 0,
            'can_view_finance' => $request->bool('can_view_finance') ? 1 : 0,
            'is_active' => $request->bool('is_active') ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => $userId]);

        Session::flash('success', 'Kullanıcı bilgileri güncellendi.');
        return Response::redirect(url('portal/ekip'));
    }
}
