<?php declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use RuntimeException;

/**
 * Class Database
 *
 * Kurumsal PDO Veritabanı Sarmalayıcısı (Singleton Pattern).
 * Hazırlıklı ifadeler (Prepared Statements), otomatik UTF-8 / UTF8MB4 bağlantısı
 * ve finansal güvenilirlik için katı hata yönetimi (Exceptions) sağlar.
 *
 * @package App\Core
 */
class Database
{
    private static ?PDO $instance = null;

    /**
     * Aktif PDO bağlantısını döndürür veya yeni bir bağlantı oluşturur.
     *
     * @return PDO
     * @throws RuntimeException
     */
    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }
        $cfg = config('database');
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $cfg['driver'],
            $cfg['host'],
            $cfg['port'],
            $cfg['database'],
            $cfg['charset']
        );
        try {
            self::$instance = new PDO($dsn, $cfg['username'], $cfg['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
            self::$instance->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
        } catch (PDOException $e) {
            throw new RuntimeException('Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(), 0, $e);
        }
        return self::$instance;
    }

    /**
     * PDO bağlantı örneğini döndürür (connection() takma adı).
     *
     * @return PDO
     */
    public static function pdo(): PDO
    {
        return self::connection();
    }

    /**
     * Hazırlıklı sorgu çalıştırır ve PDOStatement döndürür.
     *
     * @param string $sql
     * @param array $params
     * @return \PDOStatement
     */
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Tek bir satır çeker (associative array veya null).
     *
     * @param string $sql
     * @param array $params
     * @return array|null
     */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * Tüm eşleşen satırları dizi olarak çeker.
     *
     * @param string $sql
     * @param array $params
     * @return array
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchColumn(string $sql, array $params = [])
    {
        return self::query($sql, $params)->fetchColumn();
    }

    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $placeholders = array_map(static fn ($c) => ':' . $c, $columns);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`, `', $columns),
            implode(', ', $placeholders)
        );
        $params = [];
        foreach ($data as $key => $value) {
            $params[':' . $key] = $value;
        }
        self::query($sql, $params);
        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = sprintf('`%s` = :set_%s', $key, $key);
            $params[':set_' . $key] = $value;
        }
        foreach ($whereParams as $key => $value) {
            $params[$key] = $value;
        }
        $sql = sprintf('UPDATE `%s` SET %s WHERE %s', $table, implode(', ', $sets), $where);
        return self::query($sql, $params)->rowCount();
    }

    public static function delete(string $table, string $where, array $params = []): int
    {
        return self::query(sprintf('DELETE FROM `%s` WHERE %s', $table, $where), $params)->rowCount();
    }

    public static function transaction(callable $callback)
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function lastInsertId(): int
    {
        return (int) self::connection()->lastInsertId();
    }
}
