<?php declare(strict_types=1);

namespace App\Core;

/**
 * Class Mailer
 *
 * E-Posta Bildirim Servisi.
 * Geliştirme ortamında gelen bildirimleri storage/logs/mail.log dosyasına
 * kurumsal formatta kaydeder.
 *
 * @package App\Core
 */
class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $logDir = BASE_PATH . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $line = sprintf(
            "[%s] TO: %s | SUBJECT: %s\n%s\n%s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            strip_tags($htmlBody),
            str_repeat('-', 60)
        );
        return (bool) @file_put_contents($logDir . '/mail.log', $line, FILE_APPEND);
    }
}
