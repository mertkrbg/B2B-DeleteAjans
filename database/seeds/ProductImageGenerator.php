<?php declare(strict_types=1);

namespace Database\Seeds;

/**
 * PHP GD ile 800x600 kurumsal ürün görselleri üretir.
 * İnternet erişimi olmasa dahi kırık görsel (naturalWidth === 0) oluşmaz.
 */
class ProductImageGenerator
{
    private const WIDTH = 800;
    private const HEIGHT = 600;

    private const PALETTES = [
        'kablolar-pano-ekipmanlari' => [[15, 23, 42], [30, 64, 175]],
        'endustriyel-armaturler'    => [[30, 27, 20], [180, 120, 23]],
        'sensorler-roleler'         => [[12, 32, 28], [16, 122, 87]],
        'baglanti-elemanlari'       => [[28, 25, 23], [120, 113, 108]],
        'elektrik-enerji'           => [[17, 24, 39], [37, 99, 235]],
        'aydinlatma-led'            => [[40, 30, 5], [217, 119, 6]],
        'otomasyon-sensor'          => [[10, 28, 24], [5, 150, 105]],
        'hirdavat-is-guvenligi'     => [[30, 20, 20], [185, 28, 28]],
    ];

    public static function generate(string $sku, string $name, string $categorySlug, string $absolutePath, string $brand = ''): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }
        $palette = self::PALETTES[$categorySlug] ?? [[15, 23, 42], [37, 99, 235]];

        $img = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        if ($img === false) {
            return false;
        }
        imageantialias($img, true);

        self::drawGradient($img, $palette[0], $palette[1]);
        self::drawGrid($img);
        self::drawIllustration($img, $categorySlug, $palette[1]);
        self::drawVignette($img);

        $white = imagecolorallocate($img, 255, 255, 255);
        $soft  = imagecolorallocate($img, 226, 232, 240);
        $muted = imagecolorallocate($img, 148, 163, 184);
        $accent = imagecolorallocate($img, 191, 219, 254);

        $bold = self::fontPath(true);
        $regular = self::fontPath(false);

        // üst etiket
        if ($bold !== null) {
            imagettftext($img, 13, 0, 48, 58, $accent, $bold, strtoupper($brand !== '' ? $brand : 'B2B ENTERPRISE'));
            imagesetthickness($img, 2);
            imageline($img, 48, 74, 220, 74, $accent);
        }

        // alt blok
        $panelTop = 432;
        $panelColor = imagecolorallocatealpha($img, 15, 23, 42, 40);
        imagefilledrectangle($img, 0, $panelTop, self::WIDTH, self::HEIGHT, $panelColor);

        if ($bold !== null) {
            imagettftext($img, 26, 0, 48, 496, $white, $bold, self::truncate($name, 44));
            imagettftext($img, 18, 0, 48, 542, $accent, $regular ?? $bold, $sku);
            imagettftext($img, 12, 0, 48, 574, $muted, $regular ?? $bold, 'Endüstriyel B2B Tedarik · Orijinal Ürün Garantisi');
        } else {
            imagestring($img, 5, 48, 476, self::ascii($name), $white);
            imagestring($img, 4, 48, 512, $sku, $accent);
        }

        if (!is_dir(dirname($absolutePath))) {
            @mkdir(dirname($absolutePath), 0775, true);
        }
        $ok = imagepng($img, $absolutePath, 6);
        imagedestroy($img);
        return (bool) $ok;
    }

    private static function drawGradient($img, array $from, array $to): void
    {
        [$r1, $g1, $b1] = $from;
        [$r2, $g2, $b2] = $to;
        for ($y = 0; $y < self::HEIGHT; $y++) {
            $t = $y / self::HEIGHT;
            $r = (int) ($r1 + ($r2 - $r1) * $t);
            $g = (int) ($g1 + ($g2 - $g1) * $t);
            $b = (int) ($b1 + ($b2 - $b1) * $t);
            $color = imagecolorallocate($img, $r, $g, $b);
            imageline($img, 0, $y, self::WIDTH, $y, $color);
        }
    }

    private static function drawGrid($img): void
    {
        $grid = imagecolorallocatealpha($img, 255, 255, 255, 108);
        for ($x = 0; $x < self::WIDTH; $x += 40) {
            imageline($img, $x, 0, $x, self::HEIGHT, $grid);
        }
        for ($y = 0; $y < self::HEIGHT; $y += 40) {
            imageline($img, 0, $y, self::WIDTH, $y, $grid);
        }
    }

    private static function drawIllustration($img, string $categorySlug, array $tone): void
    {
        $cx = 545;
        $cy = 250;
        $light = imagecolorallocatealpha($img, 255, 255, 255, 30);
        $solid = imagecolorallocate($img, min(255, $tone[0] + 60), min(255, $tone[1] + 60), min(255, $tone[2] + 60));
        $line = imagecolorallocatealpha($img, 255, 255, 255, 70);
        imagesetthickness($img, 4);

        switch ($categorySlug) {
            case 'kablolar-pano-ekipmanlari':
            case 'elektrik-enerji':
                for ($i = 0; $i < 4; $i++) {
                    $r = 150 - $i * 26;
                    imageellipse($img, $cx, $cy, $r * 2, $r * 2, $line);
                }
                imagefilledellipse($img, $cx, $cy, 120, 120, $solid);
                imagefilledellipse($img, $cx, $cy, 60, 60, $light);
                imageline($img, $cx + 30, $cy - 30, $cx + 200, $cy - 150, $line);
                break;
            case 'endustriyel-armaturler':
            case 'aydinlatma-led':
                $pts = [];
                for ($i = 0; $i < 6; $i++) {
                    $ang = deg2rad(60 * $i - 30);
                    $pts[] = (int) ($cx + 150 * cos($ang));
                    $pts[] = (int) ($cy + 150 * sin($ang));
                }
                imagefilledpolygon($img, $pts, $light);
                imagepolygon($img, $pts, $line);
                imagefilledellipse($img, $cx, $cy, 110, 110, $solid);
                for ($i = 0; $i < 8; $i++) {
                    $ang = deg2rad(45 * $i);
                    imageline($img, $cx, $cy, (int) ($cx + 210 * cos($ang)), (int) ($cy + 210 * sin($ang)), $line);
                }
                break;
            case 'sensorler-roleler':
            case 'otomasyon-sensor':
                imagefilledrectangle($img, $cx - 150, $cy - 110, $cx + 150, $cy + 110, $light);
                imagerectangle($img, $cx - 150, $cy - 110, $cx + 150, $cy + 110, $line);
                for ($i = -1; $i <= 1; $i++) {
                    imagefilledellipse($img, $cx + $i * 80, $cy, 46, 46, $solid);
                }
                for ($x = $cx - 120; $x <= $cx + 120; $x += 30) {
                    imageline($img, $x, $cy + 60, $x, $cy + 110, $line);
                }
                break;
            default:
                for ($i = 0; $i < 6; $i++) {
                    $ang = deg2rad(60 * $i);
                    $x = (int) ($cx + 130 * cos($ang));
                    $y = (int) ($cy + 130 * sin($ang));
                    imagefilledellipse($img, $x, $y, 40, 40, $solid);
                }
                imagefilledellipse($img, $cx, $cy, 160, 160, $light);
                imageellipse($img, $cx, $cy, 160, 160, $line);
                break;
        }
    }

    private static function drawVignette($img): void
    {
        $dark = imagecolorallocatealpha($img, 0, 0, 0, 85);
        for ($i = 0; $i < 60; $i++) {
            imagerectangle($img, $i, $i, self::WIDTH - $i, self::HEIGHT - $i, $dark);
        }
    }

    private static function fontPath(bool $bold): ?string
    {
        $candidates = $bold
            ? ['C:\\Windows\\Fonts\\segoeuib.ttf', 'C:\\Windows\\Fonts\\arialbd.ttf', 'C:\\Windows\\Fonts\\arial.ttf']
            : ['C:\\Windows\\Fonts\\segoeui.ttf', 'C:\\Windows\\Fonts\\arial.ttf'];
        foreach ($candidates as $font) {
            if (is_file($font)) {
                return $font;
            }
        }
        return null;
    }

    private static function truncate(string $text, int $max): string
    {
        if (mb_strlen($text) <= $max) {
            return $text;
        }
        return mb_substr($text, 0, $max - 1) . '…';
    }

    private static function ascii(string $text): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        return $converted !== false ? $converted : $text;
    }
}
