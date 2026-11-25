<?php declare(strict_types=1);

namespace App\Core;

use PDO;
use RuntimeException;
use Throwable;

/**
 * B2B Enterprise Portal - Kurulum Yöneticisi
 *
 * Bu sınıf; gereksinim denetimi, veritabanı bağlantısı, şema migrasyonu,
 * örnek veri yükleme, yönetici hesabı oluşturma ve kurulum kilidi işlemlerini yürütür.
 * Kurulum bir kez tamamlandığında storage/installed.lock dosyası oluşturulur ve
 * sistem bir daha kurulum ekranına izin vermez.
 */
class Installer
{
    public const VERSION = '1.0.0';
    private const LOCK_RELATIVE = 'storage/installed.lock';
    private const MIN_PHP = '8.2.0';

    private static ?array $stateCache = null;

    public static function lockPath(): string
    {
        return BASE_PATH . '/' . self::LOCK_RELATIVE;
    }

    public static function isInstalled(): bool
    {
        return is_file(self::lockPath());
    }

    public static function state(): array
    {
        if (self::$stateCache !== null) {
            return self::$stateCache;
        }
        if (!self::isInstalled()) {
            return self::$stateCache = [];
        }
        $raw = @file_get_contents(self::lockPath());
        $data = $raw !== false ? json_decode($raw, true) : null;
        return self::$stateCache = is_array($data) ? $data : [];
    }

    /**
     * Ortam ve dosya sistemi gereksinimlerini denetler.
     * Her öğe: label, value, ok, required
     *
     * @return array<int, array{label:string, value:string, ok:bool, required:bool}>
     */
    public static function requirements(): array
    {
        $checks = [];

        $checks[] = [
            'label'    => 'PHP Sürümü (≥ ' . self::MIN_PHP . ')',
            'value'    => PHP_VERSION,
            'ok'       => version_compare(PHP_VERSION, self::MIN_PHP, '>='),
            'required' => true,
        ];

        $extensions = [
            'pdo'        => 'PDO',
            'pdo_mysql'  => 'PDO MySQL',
            'mbstring'   => 'mbstring',
            'bcmath'     => 'bcmath',
            'gd'         => 'GD',
            'intl'       => 'intl',
            'fileinfo'   => 'fileinfo',
            'json'       => 'JSON',
            'openssl'    => 'OpenSSL',
            'session'    => 'Session',
        ];
        foreach ($extensions as $ext => $label) {
            $loaded = extension_loaded($ext);
            $checks[] = [
                'label'    => 'Eklenti: ' . $label,
                'value'    => $loaded ? 'Yüklü' : 'Eksik',
                'ok'       => $loaded,
                'required' => true,
            ];
        }

        if (extension_loaded('zip')) {
            $checks[] = ['label' => 'Eklenti: ZIP', 'value' => 'Yüklü', 'ok' => true, 'required' => false];
        } else {
            $checks[] = ['label' => 'Eklenti: ZIP (önerilen)', 'value' => 'Eksik', 'ok' => false, 'required' => false];
        }

        $dirs = [
            'storage'                         => 'storage/',
            'storage/logs'                    => 'storage/logs/',
            'storage/uploads'                 => 'storage/uploads/',
            'public/assets/images/products'   => 'public/assets/images/products/',
        ];
        foreach ($dirs as $dir => $label) {
            $path = BASE_PATH . '/' . $dir;
            if (!is_dir($path)) {
                @mkdir($path, 0775, true);
            }
            $ok = is_dir($path) && is_writable($path);
            $checks[] = [
                'label'    => 'Yazılabilir: ' . $label,
                'value'    => $ok ? 'Yazılabilir' : 'Yazılamıyor',
                'ok'       => $ok,
                'required' => true,
            ];
        }

        $configDir = BASE_PATH . '/config';
        $checks[] = [
            'label'    => 'Yazılabilir: config/ (veritabanı ayarı)',
            'value'    => is_dir($configDir) && is_writable($configDir) ? 'Yazılabilir' : 'Yazılamıyor',
            'ok'       => is_dir($configDir) && is_writable($configDir),
            'required' => true,
        ];

        $dbConfig = BASE_PATH . '/config/database.php';
        if (is_file($dbConfig)) {
            $checks[] = [
                'label'    => 'Yazılabilir: config/database.php',
                'value'    => is_writable($dbConfig) ? 'Yazılabilir' : 'Yazılamıyor',
                'ok'       => is_writable($dbConfig),
                'required' => true,
            ];
        }

        return $checks;
    }

    /**
     * @param array<int, array{ok:bool, required:bool}> $requirements
     */
    public static function requirementsMet(array $requirements = []): bool
    {
        $requirements = $requirements ?: self::requirements();
        foreach ($requirements as $req) {
            if (($req['required'] ?? false) && !($req['ok'] ?? false)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Kurulum formu doğrulaması.
     *
     * @return array<string, string> alan => hata mesajı
     */
    public static function validate(array $input): array
    {
        $errors = [];

        $host = trim((string) ($input['db_host'] ?? ''));
        $port = trim((string) ($input['db_port'] ?? ''));
        $name = trim((string) ($input['db_name'] ?? ''));
        $user = trim((string) ($input['db_user'] ?? ''));

        if ($host === '') {
            $errors['db_host'] = 'Veritabanı sunucusu zorunludur.';
        }
        if ($port === '' || !ctype_digit($port) || (int) $port < 1 || (int) $port > 65535) {
            $errors['db_port'] = 'Geçerli bir port giriniz (1-65535).';
        }
        if ($name === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            $errors['db_name'] = 'Veritabanı adı yalnızca harf, rakam ve alt çizgi içermelidir.';
        }
        if ($user === '') {
            $errors['db_user'] = 'Veritabanı kullanıcı adı zorunludur.';
        }

        $appUrl = trim((string) ($input['app_url'] ?? ''));
        if ($appUrl === '' || filter_var($appUrl, FILTER_VALIDATE_URL) === false) {
            $errors['app_url'] = 'Geçerli bir uygulama adresi giriniz (örn. https://b2b.firma.com).';
        }

        $adminName = trim((string) ($input['admin_name'] ?? ''));
        $adminEmail = trim((string) ($input['admin_email'] ?? ''));
        $password = (string) ($input['admin_password'] ?? '');
        $confirm = (string) ($input['admin_password_confirm'] ?? '');

        if ($adminName === '') {
            $errors['admin_name'] = 'Yönetici adı zorunludur.';
        }
        if ($adminEmail === '' || filter_var($adminEmail, FILTER_VALIDATE_EMAIL) === false) {
            $errors['admin_email'] = 'Geçerli bir yönetici e-posta adresi giriniz.';
        }
        if (strlen($password) < 8) {
            $errors['admin_password'] = 'Şifre en az 8 karakter olmalıdır.';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $errors['admin_password'] = 'Şifre en az bir harf ve bir rakam içermelidir.';
        }
        if ($password !== $confirm) {
            $errors['admin_password_confirm'] = 'Şifreler eşleşmiyor.';
        }

        $companyTitle = trim((string) ($input['company_title'] ?? ''));
        if ($companyTitle === '') {
            $errors['company_title'] = 'Firma unvanı zorunludur.';
        }

        return $errors;
    }

    /**
     * Veritabanına bağlanmayı dener.
     *
     * @return array{ok:bool, message:string}
     */
    public static function testConnection(array $db): array
    {
        try {
            $dsn = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $db['host'], $db['port']);
            $pdo = new PDO($dsn, (string) $db['user'], (string) $db['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ]);
            $pdo->query('SELECT 1');
            return ['ok' => true, 'message' => 'Bağlantı başarılı.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Kurulumu uçtan uca yürütür ve kilit dosyasını yazar.
     *
     * @return array<string, mixed> kurulum özeti
     */
    public static function install(array $input): array
    {
        if (self::isInstalled()) {
            throw new RuntimeException('Sistem zaten kurulu. Yeniden kurulum yapılamaz.');
        }

        $requirements = self::requirements();
        if (!self::requirementsMet($requirements)) {
            throw new RuntimeException('Sunucu gereksinimleri karşılanmıyor. Lütfen eksik eklenti/izinleri giderin.');
        }

        $errors = self::validate($input);
        if ($errors !== []) {
            throw new RuntimeException((string) reset($errors));
        }

        $db = [
            'host'   => trim((string) $input['db_host']),
            'port'   => (int) $input['db_port'],
            'name'   => trim((string) $input['db_name']),
            'user'   => trim((string) $input['db_user']),
            'pass'   => (string) ($input['db_pass'] ?? ''),
        ];
        $appUrl = rtrim(trim((string) $input['app_url']), '/');
        $admin = [
            'name'  => trim((string) $input['admin_name']),
            'email' => strtolower(trim((string) $input['admin_email'])),
            'pass'  => (string) $input['admin_password'],
        ];
        $companyTitle = trim((string) $input['company_title']);
        $demoData = !empty($input['demo_data']);

        $pdo = self::connectAndPrepareDatabase($db);

        // 1) Şema migrasyonu
        $migrations = self::runMigrations($pdo);

        // 2) Örnek veri (isteğe bağlı)
        if ($demoData) {
            self::seedDemo($pdo);
        }

        // 3) Yönetici hesabı (her durumda sağlanan bilgilerle garanti edilir)
        self::upsertAdmin($pdo, $admin);

        // 4) Varsayılan sistem ayarları (demo yoksa veya eksikse)
        self::ensureDefaultSettings($pdo, $companyTitle, $admin['email']);

        // 5) Veritabanı konfigürasyonunu yaz
        self::writeDatabaseConfig($db);

        // 6) Kurulum kilidi
        $state = [
            'installed_at' => date('Y-m-d H:i:s'),
            'app_url'      => $appUrl,
            'admin_email'  => $admin['email'],
            'demo_data'    => $demoData,
            'php_version'  => PHP_VERSION,
            'app_key'      => bin2hex(random_bytes(32)),
            'version'      => self::VERSION,
            'migrations'   => count($migrations),
        ];
        self::writeLock($state);
        self::$stateCache = $state;

        return [
            'state'      => $state,
            'admin'      => $admin,
            'migrations' => $migrations,
            'app_url'    => $appUrl,
            'demo_data'  => $demoData,
        ];
    }

    private static function connectAndPrepareDatabase(array $db): PDO
    {
        try {
            $dsnServer = sprintf('mysql:host=%s;port=%s;charset=utf8mb4', $db['host'], $db['port']);
            $pdo = new PDO($dsnServer, $db['user'], $db['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT            => 8,
            ]);
        } catch (Throwable $e) {
            throw new RuntimeException('MySQL bağlantısı kurulamadı: ' . $e->getMessage());
        }

        $exists = false;
        try {
            $stmt = $pdo->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :n');
            $stmt->execute([':n' => $db['name']]);
            $exists = $stmt->fetchColumn() !== false;
        } catch (Throwable $e) {
            // information_schema okunamazsa doğrudan kullanmayı deneyeceğiz
        }

        if (!$exists) {
            try {
                $pdo->exec(sprintf(
                    'CREATE DATABASE IF NOT EXISTS `%s` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci',
                    $db['name']
                ));
            } catch (Throwable $e) {
                throw new RuntimeException('Veritabanı oluşturulamadı: ' . $e->getMessage());
            }
        }

        try {
            $pdo->exec(sprintf('USE `%s`', $db['name']));
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("SET sql_mode = 'STRICT_ALL_TABLES,NO_ENGINE_SUBSTITUTION'");
        } catch (Throwable $e) {
            throw new RuntimeException('Veritabanı seçilemedi: ' . $e->getMessage());
        }

        return $pdo;
    }

    /**
     * @return array<int, string>
     */
    private static function runMigrations(PDO $pdo): array
    {
        $dir = BASE_PATH . '/database/migrations';
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);

        if ($files === []) {
            throw new RuntimeException('Migrasyon dosyaları bulunamadı.');
        }

        $applied = [];
        foreach ($files as $file) {
            $sql = file_get_contents($file);
            if ($sql === false || trim($sql) === '') {
                continue;
            }
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                throw new RuntimeException('Migrasyon hatası (' . basename($file) . '): ' . $e->getMessage(), 0, $e);
            }
            $applied[] = basename($file);
        }

        return $applied;
    }

    private static function seedDemo(PDO $pdo): void
    {
        $seeder = require BASE_PATH . '/database/seeds/SeedRunner.php';
        if (!is_callable($seeder)) {
            throw new RuntimeException('Örnek veri yükleyici çalıştırılamadı.');
        }
        try {
            $seeder($pdo);
        } catch (Throwable $e) {
            throw new RuntimeException('Örnek veri yüklenirken hata oluştu: ' . $e->getMessage(), 0, $e);
        }
    }

    private static function upsertAdmin(PDO $pdo, array $admin): void
    {
        $now = date('Y-m-d H:i:s');
        $hash = password_hash($admin['pass'], PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $admin['email']]);
        $existingId = $stmt->fetchColumn();

        if ($existingId !== false) {
            $update = $pdo->prepare(
                'UPDATE users SET company_id = NULL, full_name = :n, password_hash = :h, role = :r,
                 is_active = 1, can_view_prices = 1, can_view_finance = 1, order_approval_limit = NULL,
                 updated_at = :u WHERE id = :id'
            );
            $update->execute([
                ':n'  => $admin['name'],
                ':h'  => $hash,
                ':r'  => 'super_admin',
                ':u'  => $now,
                ':id' => (int) $existingId,
            ]);
            return;
        }

        $insert = $pdo->prepare(
            'INSERT INTO users (company_id, full_name, email, phone, password_hash, role,
             order_approval_limit, can_view_prices, can_view_finance, is_active, created_at, updated_at)
             VALUES (NULL, :n, :e, NULL, :h, :r, NULL, 1, 1, 1, :c, :u)'
        );
        $insert->execute([
            ':n' => $admin['name'],
            ':e' => $admin['email'],
            ':h' => $hash,
            ':r' => 'super_admin',
            ':c' => $now,
            ':u' => $now,
        ]);
    }

    private static function ensureDefaultSettings(PDO $pdo, string $companyTitle, string $adminEmail): void
    {
        $defaults = [
            ['company_title', $companyTitle, 'general'],
            ['support_email', $adminEmail, 'general'],
            ['min_order_note', 'Minimum sipariş tutarı 500 TL\'dir.', 'general'],
            ['default_vat_rate', '20', 'tax'],
            ['shipping_cost', '250.00', 'shipping'],
            ['bank_name', '', 'payment'],
            ['bank_iban', '', 'payment'],
        ];
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO system_settings (setting_key, setting_value, group_name, updated_at)
             VALUES (:k, :v, :g, :u)'
        );
        $now = date('Y-m-d H:i:s');
        foreach ($defaults as [$key, $value, $group]) {
            $stmt->execute([':k' => $key, ':v' => $value, ':g' => $group, ':u' => $now]);
        }
    }

    private static function writeDatabaseConfig(array $db): void
    {
        $payload = [
            'driver'    => 'mysql',
            'host'      => $db['host'],
            'port'      => $db['port'],
            'database'  => $db['name'],
            'username'  => $db['user'],
            'password'  => $db['pass'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
        $content = "<?php declare(strict_types=1);\n\n// Bu dosya kurulum sihirbazı tarafından otomatik oluşturuldu.\nreturn "
            . var_export($payload, true) . ";\n";

        $target = BASE_PATH . '/config/database.php';
        if (@file_put_contents($target, $content, LOCK_EX) === false) {
            throw new RuntimeException('config/database.php dosyası yazılamadı. Dosya izinlerini kontrol edin.');
        }
    }

    private static function writeLock(array $state): void
    {
        $json = json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new RuntimeException('Kurulum kilidi oluşturulamadı.');
        }
        $dir = BASE_PATH . '/storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (@file_put_contents(self::lockPath(), $json, LOCK_EX) === false) {
            throw new RuntimeException('Kurulum kilidi yazılamadı (storage/ yazılabilir mi?).');
        }
    }
}
