<?php declare(strict_types=1);

/**
 * B2B Enterprise Portal - Front Controller
 *
 * Tüm HTTP isteklerinin karşılandığı ana giriş noktası.
 * Çekirdek autoloader'ı yükler, servisleri başlatır ve isteği yönlendirir.
 *
 * @package B2BEnterprise
 */

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Application.php';

\App\Core\Application::registerAutoloader();

require BASE_PATH . '/app/Core/helpers.php';

$app = new \App\Core\Application();
$app->boot();
$app->run(new \App\Core\Request());
