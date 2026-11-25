<?php declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Class Application
 *
 * B2B Enterprise Portal Çekirdek Uygulama Yöneticisi.
 * PSR-4 autoloader, rotalama, hata yakalama (global exception handler)
 * ve kurulum koruma döngüsünü koordine eder.
 *
 * @package App\Core
 */
class Application
{
    private Router $router;

    /**
     * Application yapıcısı.
     */
    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * PSR-4 standartlarında App\ ad alanı için yerel autoloader kaydeder.
     *
     * @return void
     */
    public static function registerAutoloader(): void
    {
        spl_autoload_register(static function (string $class): void {
            $prefix = 'App\\';
            $baseDir = BASE_PATH . '/app/';
            if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
                return;
            }
            $relative = substr($class, strlen($prefix));
            $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }

    /**
     * Uygulama çekirdek servislerini, zaman dilimini, oturumu ve hata yönetimini başlatır.
     *
     * @return void
     */
    public function boot(): void
    {
        date_default_timezone_set((string) config('app.timezone', 'Europe/Istanbul'));
        mb_internal_encoding('UTF-8');
        View::setPath(BASE_PATH . '/resources/views');
        Session::start();
        $this->registerErrorHandling();
    }

    /**
     * Genel hata ve istisna yakalayıcıyı yapılandırır.
     *
     * @return void
     */
    private function registerErrorHandling(): void
    {
        $debug = (bool) config('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        set_exception_handler(function (Throwable $e) use ($debug): void {
            $logDir = BASE_PATH . '/storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0775, true);
            }
            @file_put_contents(
                $logDir . '/app-error.log',
                sprintf("[%s] %s: %s in %s:%d\n%s\n", date('Y-m-d H:i:s'), get_class($e), $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString()),
                FILE_APPEND
            );
            http_response_code(500);
            if (!headers_sent()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
            $home = url('portal');
            echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">'
                . '<title>Sunucu Hatası · B2B Enterprise</title>'
                . '<link rel="icon" type="image/svg+xml" href="' . e(url('assets/images/logo.svg')) . '">'
                . '<style>body{margin:0;font-family:Inter,system-ui,Segoe UI,Arial,sans-serif;background:#F8FAFC;color:#0F172A;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px}'
                . '.box{max-width:520px;width:100%;background:#fff;border:1px solid #E2E8F0;border-radius:18px;padding:32px;text-align:center;box-shadow:0 24px 60px -20px rgba(15,23,42,.35)}'
                . '.badge{width:64px;height:64px;margin:0 auto;border-radius:16px;background:#FEE2E2;color:#991B1B;display:flex;align-items:center;justify-content:center;font-size:30px;font-weight:700}'
                . 'h1{font-size:20px;margin:16px 0 8px}p{color:#64748B;font-size:14px;line-height:1.6;margin:0}'
                . 'a{display:inline-block;margin-top:20px;background:#2563EB;color:#fff;text-decoration:none;padding:10px 20px;border-radius:12px;font-weight:600;font-size:14px}'
                . '</style></head><body><div class="box"><div class="badge">!</div>'
                . '<h1>Beklenmeyen Bir Hata Oluştu</h1>'
                . '<p>İşleminiz sırasında teknik bir sorun meydana geldi. Ekibimiz bilgilendirildi. Lütfen birkaç dakika sonra tekrar deneyin.</p>'
                . '<a href="' . e($home) . '">Portala Dön</a></div></body></html>';
        });
    }

    /**
     * Rota tanımlarını routes.php dosyasından yükler ve router'a kaydeder.
     *
     * @return void
     */
    public function loadRoutes(): void
    {
        $routesFile = BASE_PATH . '/app/routes.php';
        if (is_file($routesFile)) {
            $register = require $routesFile;
            if (is_callable($register)) {
                $register($this->router);
            }
        }
    }

    /**
     * Gelen HTTP isteğini işler, kurulum kilidini doğrular ve rotayı çalıştırır.
     *
     * @param Request $request
     * @return void
     */
    public function run(Request $request): void
    {
        if (!Installer::isInstalled()) {
            $path = $request->path();
            $isAsset = str_starts_with($path, '/assets');
            $isInstaller = $path === '/kurulum' || $path === '/kurulum/';
            if (!$isAsset && !$isInstaller) {
                $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
                $dir = dirname($script);
                if ($dir === '/' || $dir === '.' || $dir === '\\') {
                    $dir = '';
                }
                header('Location: ' . rtrim($dir, '/') . '/kurulum', true, 302);
                exit;
            }
        }

        $this->loadRoutes();
        $response = $this->router->dispatch($request);
        $response->send();
    }

    public function router(): Router
    {
        return $this->router;
    }
}
