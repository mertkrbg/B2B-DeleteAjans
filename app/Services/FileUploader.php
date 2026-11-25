<?php declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Class FileUploader
 *
 * Güvenli Dosya Yükleme Servisi.
 * MIME-type denetimi (finfo), uzantı doğrulaması, dosya boyutu sınırı
 * ve rastgele benzersiz dosya adı üretimi ile güvenli depolama sağlar.
 *
 * @package App\Services
 */
class FileUploader
{
    public static function store(array $file, string $subdir): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Dosya yüklenemedi. Lütfen tekrar deneyin.');
        }
        if (($file['size'] ?? 0) > (int) config('app.upload_max', 5242880)) {
            throw new RuntimeException('Dosya boyutu izin verilen sınırı aşıyor (maks 5 MB).');
        }
        $allowedMimes = (array) config('app.allowed_upload_mimes', []);
        $allowedExt = (array) config('app.allowed_upload_ext', []);
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($file['tmp_name']);
        if (!in_array($mime, $allowedMimes, true)) {
            throw new RuntimeException('Geçersiz dosya türü. Yalnızca PDF, JPG veya PNG yükleyebilirsiniz.');
        }
        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) {
            throw new RuntimeException('Geçersiz dosya uzantısı.');
        }
        $dir = BASE_PATH . '/storage/uploads/' . trim($subdir, '/');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Yükleme dizini oluşturulamadı.');
        }
        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $target = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new RuntimeException('Dosya kaydedilemedi.');
        }
        return 'storage/uploads/' . trim($subdir, '/') . '/' . $filename;
    }
}
