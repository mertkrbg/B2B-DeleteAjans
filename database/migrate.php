<?php declare(strict_types=1);

/**
 * B2B Enterprise Portal - CLI migrasyon ve seed çalıştırıcı
 *
 * Kullanım:
 *   php database/migrate.php            -> Şemayı oluşturur (eksik tabloları)
 *   php database/migrate.php seed       -> Şema + örnek veri
 *   php database/migrate.php fresh      -> Veritabanını sıfırlar + şema + örnek veri
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Bu betik yalnızca komut satırından çalıştırılabilir.\n");
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));

$config = require BASE_PATH . '/config/database.php';
$args = array_slice($argv, 1);
$fresh = in_array('fresh', $args, true);
$seed = $fresh || in_array('seed', $args, true);

function out(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

$dsnServer = sprintf('mysql:host=%s;port=%s;charset=%s', $config['host'], $config['port'], $config['charset']);

try {
    $pdo = new PDO($dsnServer, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "MySQL bağlantı hatası: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

$dbName = $config['database'];
if ($fresh) {
    $pdo->exec(sprintf('DROP DATABASE IF EXISTS `%s`', $dbName));
    out("Veritabanı sıfırlandı: {$dbName}");
}
$pdo->exec(sprintf(
    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
    $dbName
));
out("Veritabanı hazır: {$dbName}");

$pdo->exec(sprintf('USE `%s`', $dbName));
$pdo->exec("SET sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");

$migrationDir = BASE_PATH . '/database/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];
sort($files);

foreach ($files as $file) {
    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        continue;
    }
    $pdo->exec($sql);
    out('  [OK] Migrasyon: ' . basename($file));
}

out('Toplam ' . count($files) . ' migrasyon dosyası uygulandı.');

if ($seed) {
    out('Örnek veriler yükleniyor...');
    $seeder = require BASE_PATH . '/database/seeds/SeedRunner.php';
    if (is_callable($seeder)) {
        $seeder($pdo);
    } else {
        fwrite(STDERR, "SeedRunner çalıştırılabilir bir kapanış döndürmedi.\n");
        exit(1);
    }
    out('Seed tamamlandı.');
}

out('İşlem başarıyla tamamlandı.');
