<?php declare(strict_types=1);

namespace App\Core;

/**
 * Class Request
 *
 * HTTP İsteği Sarmalayıcısı.
 * GET, POST, JSON gövdesi, başlıklar ve yüklenen dosyaları (FILES)
 * güvenli bir şekilde filtreleyip erişim sağlar.
 *
 * @package App\Core
 */
class Request
{
    private string $method;
    private string $path;
    private array $query;
    private array $post;
    private array $files;
    private array $server;
    private ?array $json = null;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $this->path = '/' . trim($path, '/');
        $this->query = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function path(): string
    {
        return $this->path;
    }

    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->post, $this->jsonData());
    }

    public function input(string $key, $default = null)
    {
        $json = $this->jsonData();
        return $this->post[$key] ?? $json[$key] ?? $this->query[$key] ?? $default;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);
        return is_string($value) ? trim($value) : $default;
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->input($key, $default);
        return is_numeric($value) ? (int) $value : $default;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->input($key, $default);
        return is_numeric($value) ? (float) $value : $default;
    }

    public function bool(string $key): bool
    {
        $value = $this->input($key);
        return in_array($value, ['1', 1, true, 'true', 'on', 'yes'], true);
    }

    public function has(string $key): bool
    {
        $all = $this->all();
        return array_key_exists($key, $all);
    }

    public function file(string $key): ?array
    {
        if (!isset($this->files[$key]) || ($this->files[$key]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        return $this->files[$key];
    }

    public function token(): ?string
    {
        $header = $this->server['HTTP_X_CSRF_TOKEN'] ?? null;
        if ($header !== null) {
            return (string) $header;
        }
        $value = $this->post['_token'] ?? $this->jsonData()['_token'] ?? null;
        return $value !== null ? (string) $value : null;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function userAgent(): string
    {
        return (string) ($this->server['HTTP_USER_AGENT'] ?? '');
    }

    public function isAjax(): bool
    {
        return strtolower((string) ($this->server['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    }

    public function jsonData(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }
        $contentType = (string) ($this->server['CONTENT_TYPE'] ?? '');
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode((string) $raw, true);
            $this->json = is_array($decoded) ? $decoded : [];
        } else {
            $this->json = [];
        }
        return $this->json;
    }
}
