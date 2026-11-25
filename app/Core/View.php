<?php declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Class View
 *
 * Şablon Görüntüleme Motoru.
 * Sayfa şablonlarını ve layout yapısını izole bir tampon bellek (output buffer)
 * üzerinde işler ve çıktı üretir.
 *
 * @package App\Core
 */
class View
{
    private static string $viewPath = '';

    public static function setPath(string $path): void
    {
        self::$viewPath = rtrim($path, '/\\');
    }

    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $templateFile = self::$viewPath . '/' . str_replace('.', '/', $template) . '.php';
        if (!is_file($templateFile)) {
            throw new RuntimeException('Şablon bulunamadı: ' . $template);
        }
        $content = self::capture($templateFile, $data);
        if ($layout === null) {
            return $content;
        }
        $layoutFile = self::$viewPath . '/layouts/' . $layout . '.php';
        if (!is_file($layoutFile)) {
            throw new RuntimeException('Layout bulunamadı: ' . $layout);
        }
        return self::capture($layoutFile, array_merge($data, ['content' => $content]));
    }

    private static function capture(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
