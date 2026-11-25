<?php declare(strict_types=1);

namespace App\Core;

/**
 * Class Session
 *
 * Güvenli Oturum (Session) ve Flash Mesaj Yöneticisi.
 * HttpOnly, Secure, SameSite=Lax tanımları, Session Hijacking önleme
 * ve CSRF token üretimini yönetir.
 *
 * @package App\Core
 */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $name = (string) config('app.session_name', 'B2B_PORTAL_SESSID');
        session_name($name);
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    public static function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, $default = null)
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    public static function flash(string $type, string $message): void
    {
        $flashes = $_SESSION['_flash'] ?? [];
        $flashes[$type][] = $message;
        $_SESSION['_flash'] = $flashes;
    }

    public static function getFlashes(): array
    {
        $flashes = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $flashes;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function validateCsrf(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['_csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    public static function isAuthenticated(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function authId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    public static function auth(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    public static function login(array $user): void
    {
        self::regenerate();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['company_id'] = isset($user['company_id']) && $user['company_id'] !== null ? (int) $user['company_id'] : null;
        $_SESSION['role'] = $user['role'] ?? null;
        $_SESSION['auth_user'] = [
            'id'         => (int) $user['id'],
            'company_id' => isset($user['company_id']) ? (int) $user['company_id'] : null,
            'full_name'  => $user['full_name'] ?? '',
            'email'      => $user['email'] ?? '',
            'role'       => $user['role'] ?? '',
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
