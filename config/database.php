<?php declare(strict_types=1);

/**
 * Veritabanı Yapılandırması (MySQL / MariaDB)
 *
 * Ortam değişkenlerini (getenv) öncelikli olarak kontrol eder,
 * tanımlanmadığı durumlarda yerel Laragon / MySQL varsayılanlarını kullanır.
 *
 * @package Config
 */

return [
    'driver'    => getenv('DB_CONNECTION') ?: 'mysql',
    'host'      => getenv('DB_HOST') ?: '127.0.0.1',
    'port'      => (int) (getenv('DB_PORT') ?: 3306),
    'database'  => getenv('DB_DATABASE') ?: 'b2b_enterprise',
    'username'  => getenv('DB_USERNAME') ?: 'root',
    'password'  => getenv('DB_PASSWORD') !== false ? getenv('DB_PASSWORD') : '',
    'charset'   => getenv('DB_CHARSET') ?: 'utf8mb4',
    'collation' => getenv('DB_COLLATION') ?: 'utf8mb4_unicode_ci',
];
