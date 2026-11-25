<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Services\FileUploader;
use RuntimeException;
use Throwable;

/**
 * Class AuthController
 *
 * Bayi ve ERP Yönetici Kimlik Doğrulama Kontrolcüsü.
 * Güvenli giriş/çıkış, şifre doğrulama (bcrypt), oturum yenileme (session fixation koruması)
 * ve evrak yüklemeli yeni bayi başvuru süreçlerini yönetir.
 *
 * @package App\Controllers
 */
class AuthController
{
    public function showLogin(Request $request): Response
    {
        if (Session::isAuthenticated()) {
            $role = (string) Session::get('role', '');
            $isAdmin = in_array($role, ['super_admin', 'sales_manager', 'finance_admin'], true);
            return Response::redirect(url($isAdmin ? 'admin' : 'portal'));
        }
        $html = View::render('auth.login', [
            'title' => 'Giriş Yap',
        ], 'auth');
        return Response::html($html);
    }

    public function login(Request $request): Response
    {
        $email = strtolower($request->string('email'));
        $password = (string) $request->input('password', '');

        $errors = Validator::validate(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required|min:6'],
            ['email' => 'E-posta', 'password' => 'Şifre']
        );

        if (!empty($errors)) {
            Validator::persistErrors($errors);
            old_input(['email' => $email]);
            Session::flash('error', 'Lütfen giriş bilgilerinizi kontrol edin.');
            return Response::redirect(url('login'));
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = :email LIMIT 1', [':email' => $email]);

        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            Session::flash('error', 'E-posta adresi veya şifre hatalı.');
            old_input(['email' => $email]);
            return Response::redirect(url('login'));
        }

        if ((int) $user['is_active'] !== 1) {
            Session::flash('error', 'Hesabınız pasif durumda. Lütfen firma yöneticiniz ile iletişime geçin.');
            return Response::redirect(url('login'));
        }

        Session::login($user);
        Database::update('users', ['last_login_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $user['id']]);
        Session::forget('_old');
        Session::forget('_errors');

        $isAdmin = in_array((string) $user['role'], ['super_admin', 'sales_manager', 'finance_admin'], true);
        Session::flash('success', 'Hoş geldiniz, ' . ($user['full_name'] ?? '') . '.');
        return Response::redirect(url($isAdmin ? 'admin' : 'portal'));
    }

    public function showRegister(Request $request): Response
    {
        $html = View::render('auth.register_b2b', [
            'title' => 'Yeni Bayi Başvurusu',
        ], 'auth');
        return Response::html($html);
    }

    public function register(Request $request): Response
    {
        $data = [
            'title'        => $request->string('title'),
            'brand_name'   => $request->string('brand_name'),
            'tax_office'   => $request->string('tax_office'),
            'tax_number'   => preg_replace('/\s+/', '', $request->string('tax_number')) ?? '',
            'mersis_no'    => $request->string('mersis_no'),
            'phone'        => $request->string('phone'),
            'email'        => strtolower($request->string('email')),
            'city'         => $request->string('city'),
            'district'     => $request->string('district'),
            'address'      => $request->string('address'),
            'contact_name' => $request->string('contact_name'),
            'password'     => (string) $request->input('password', ''),
            'password_confirmation' => (string) $request->input('password_confirmation', ''),
        ];

        $errors = Validator::validate($data, [
            'title'        => 'required|min:3|max:255',
            'tax_office'   => 'required|max:100',
            'tax_number'   => 'required|tax_number|unique:companies,tax_number',
            'phone'        => 'required|phone',
            'email'        => 'required|email|max:180|unique:users,email',
            'city'         => 'required|max:60',
            'district'     => 'required|max:60',
            'address'      => 'required|min:10',
            'contact_name' => 'required|min:3|max:150',
            'password'     => 'required|min:8|confirmed',
        ], [
            'title' => 'Firma unvanı', 'tax_office' => 'Vergi dairesi', 'tax_number' => 'Vergi numarası',
            'phone' => 'Telefon', 'email' => 'E-posta', 'city' => 'İl', 'district' => 'İlçe',
            'address' => 'Adres', 'contact_name' => 'Yetkili adı', 'password' => 'Şifre',
        ]);

        $file = $request->file('tax_plate');
        if ($file === null) {
            $errors['tax_plate'] = 'Vergi levhası belgesi yüklemek zorunludur (PDF/JPG/PNG).';
        }

        if (!empty($errors)) {
            Validator::persistErrors($errors);
            old_input(array_merge($data, ['_token' => '']));
            Session::flash('error', 'Lütfen formdaki hataları düzeltin.');
            return Response::redirect(url('bayi-basvuru'));
        }

        try {
            $storedPath = FileUploader::store($file, 'tax-plates');
        } catch (RuntimeException $e) {
            Validator::persistErrors(['tax_plate' => $e->getMessage()]);
            old_input(array_merge($data, ['_token' => '']));
            Session::flash('error', $e->getMessage());
            return Response::redirect(url('bayi-basvuru'));
        }

        try {
            $companyId = Database::transaction(function () use ($data, $storedPath): int {
                $year = date('Y');
                $seq = (int) Database::fetchColumn('SELECT COUNT(*) FROM companies') + 1;
                $companyCode = sprintf('BY-%s-%04d', $year, $seq);

                $companyId = Database::insert('companies', [
                    'company_code'      => $companyCode,
                    'title'             => $data['title'],
                    'brand_name'        => $data['brand_name'] !== '' ? $data['brand_name'] : null,
                    'tax_office'        => $data['tax_office'],
                    'tax_number'        => $data['tax_number'],
                    'mersis_no'         => $data['mersis_no'] !== '' ? $data['mersis_no'] : null,
                    'tier_level'        => 'bronze',
                    'base_discount_rate'=> 0.00,
                    'credit_limit'      => 0.0000,
                    'current_balance'   => 0.0000,
                    'overdue_balance'   => 0.0000,
                    'payment_term_days' => 0,
                    'status'            => 'pending',
                    'tax_plate_file'    => $storedPath,
                    'phone'             => $data['phone'],
                    'email'             => $data['email'],
                    'city'              => $data['city'],
                    'district'          => $data['district'],
                    'address'           => $data['address'],
                    'created_at'        => date('Y-m-d H:i:s'),
                    'updated_at'        => date('Y-m-d H:i:s'),
                ]);

                Database::insert('company_addresses', [
                    'company_id'       => $companyId,
                    'address_type'     => 'both',
                    'title'            => 'Merkez Adres',
                    'contact_name'     => $data['contact_name'],
                    'contact_phone'    => $data['phone'],
                    'city'             => $data['city'],
                    'district'         => $data['district'],
                    'full_address'     => $data['address'],
                    'is_default'       => 1,
                ]);

                Database::insert('users', [
                    'company_id'    => $companyId,
                    'full_name'     => $data['contact_name'],
                    'email'         => $data['email'],
                    'phone'         => $data['phone'],
                    'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                    'role'          => 'company_owner',
                    'order_approval_limit' => null,
                    'can_view_prices' => 1,
                    'can_view_finance' => 1,
                    'is_active'      => 1,
                    'created_at'    => date('Y-m-d H:i:s'),
                    'updated_at'    => date('Y-m-d H:i:s'),
                ]);

                return $companyId;
            });
        } catch (Throwable $e) {
            Session::flash('error', 'Başvuru kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.');
            old_input(array_merge($data, ['_token' => '']));
            return Response::redirect(url('bayi-basvuru'));
        }

        Session::forget('_old');
        Session::forget('_errors');
        Session::flash('success', 'Bayi başvurunuz alındı. Yönetici onayından sonra e-posta ile bilgilendirileceksiniz. Başvuru No: BY-' . date('Y') . '-' . str_pad((string) $companyId, 4, '0', STR_PAD_LEFT));
        return Response::redirect(url('login'));
    }

    public function logout(Request $request): Response
    {
        Session::logout();
        session_start();
        Session::flash('success', 'Güvenli bir şekilde çıkış yaptınız.');
        return Response::redirect(url('login'));
    }
}
