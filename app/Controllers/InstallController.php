<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Installer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use Throwable;

/**
 * Class InstallController
 *
 * Web Tabanlı Kurulum Sihirbazı Kontrolcüsü.
 * Sunucu gereksinim denetimi, veritabanı bağlantı testi, otomatik şema migrasyonu,
 * seed veri yükleme, süper yönetici hesabı oluşturma ve sistem kilitleme akışını yönetir.
 *
 * @package App\Controllers
 */
class InstallController
{
    public function index(Request $request): Response
    {
        if (Installer::isInstalled()) {
            $html = View::render('install.locked', [
                'title' => 'Kurulum Tamamlandı',
                'state' => Installer::state(),
            ]);
            return Response::html($html, 403);
        }

        $requirements = Installer::requirements();
        $html = View::render('install.index', [
            'title'        => 'Kurulum Sihirbazı',
            'requirements' => $requirements,
            'canInstall'   => Installer::requirementsMet($requirements),
            'detectedUrl'  => $this->detectedUrl(),
            'errors'       => [],
        ]);
        return Response::html($html);
    }

    public function install(Request $request): Response
    {
        if (Installer::isInstalled()) {
            $html = View::render('install.locked', [
                'title' => 'Kurulum Tamamlandı',
                'state' => Installer::state(),
            ]);
            return Response::html($html, 403);
        }

        $input = [
            'db_host'               => (string) $request->input('db_host', ''),
            'db_port'               => (string) $request->input('db_port', '3306'),
            'db_name'               => (string) $request->input('db_name', ''),
            'db_user'               => (string) $request->input('db_user', ''),
            'db_pass'               => (string) $request->input('db_pass', ''),
            'app_url'               => (string) $request->input('app_url', $this->detectedUrl()),
            'admin_name'            => (string) $request->input('admin_name', ''),
            'admin_email'           => (string) $request->input('admin_email', ''),
            'admin_password'        => (string) $request->input('admin_password', ''),
            'admin_password_confirm'=> (string) $request->input('admin_password_confirm', ''),
            'company_title'         => (string) $request->input('company_title', ''),
            'demo_data'             => $request->input('demo_data', '') !== '' ? '1' : '',
        ];

        $requirements = Installer::requirements();
        if (!Installer::requirementsMet($requirements)) {
            return $this->renderForm($input, ['genel' => 'Sunucu gereksinimleri karşılanmıyor. Eksik eklenti veya dosya izinlerini giderin.'], $requirements, 422);
        }

        $errors = Installer::validate($input);
        if ($errors !== []) {
            return $this->renderForm($input, $errors, $requirements, 422);
        }

        try {
            $result = Installer::install($input);
        } catch (Throwable $e) {
            return $this->renderForm(
                $input,
                ['genel' => $e->getMessage()],
                $requirements,
                500
            );
        }

        // Kurulum başarılı: oturumu temizle ve başarı ekranını göster.
        Session::logout();
        Session::start();

        $html = View::render('install.success', [
            'title'     => 'Kurulum Tamamlandı',
            'admin'     => $result['admin'],
            'appUrl'    => $result['app_url'],
            'demoData'  => $result['demo_data'],
            'state'     => $result['state'],
        ]);
        return Response::html($html);
    }

    private function renderForm(array $input, array $errors, array $requirements, int $status): Response
    {
        $old = array_merge([
            'db_host'    => '127.0.0.1',
            'db_port'    => '3306',
            'db_name'    => 'b2b_enterprise',
            'db_user'    => 'root',
            'db_pass'    => '',
            'app_url'    => $this->detectedUrl(),
            'admin_name' => '',
            'admin_email'=> '',
            'company_title' => 'B2B Enterprise Tedarik A.Ş.',
            'demo_data'  => '1',
        ], array_filter($input, static fn ($v) => $v !== ''));

        $html = View::render('install.index', [
            'title'        => 'Kurulum Sihirbazı',
            'requirements' => $requirements,
            'canInstall'   => Installer::requirementsMet($requirements),
            'detectedUrl'  => $this->detectedUrl(),
            'errors'       => $errors,
            'old'          => $old,
        ]);
        return Response::html($html, $status);
    }

    private function detectedUrl(): string
    {
        $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
        $scheme = $https ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');

        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir = str_replace('\\', '/', dirname($script));
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }

        return $scheme . '://' . $host . rtrim($dir, '/');
    }
}
