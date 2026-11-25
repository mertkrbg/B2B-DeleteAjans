<?php declare(strict_types=1);

/**
 * B2B Enterprise Portal - Genel Yardımcı Fonksiyonlar (Helper Functions)
 *
 * Güvenli HTML kaçışlama e(), para/döviz formatlama format_money(),
 * finansal bcmath hesaplamaları, URL üretimi, oturum ve flash mesajları
 * sağlayan global yardımcı fonksiyon kümesi.
 *
 * @package App\Core
 */

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('config')) {
    function config(string $key, $default = null)
    {
        static $configs = [];
        $parts = explode('.', $key);
        $file = array_shift($parts);
        if (!isset($configs[$file])) {
            $path = BASE_PATH . '/config/' . $file . '.php';
            $configs[$file] = is_file($path) ? require $path : [];
        }
        $value = $configs[$file];
        foreach ($parts as $part) {
            if (is_array($value) && array_key_exists($part, $value)) {
                $value = $value[$part];
            } else {
                return $default;
            }
        }
        return $value;
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        static $base = null;
        if ($base === null) {
            $state = \App\Core\Installer::state();
            $resolved = trim((string) ($state['app_url'] ?? ''));
            if ($resolved === '') {
                if (PHP_SAPI === 'cli') {
                    $resolved = (string) config('app.url', 'http://b2b.test');
                } else {
                    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== '' && $_SERVER['HTTPS'] !== 'off';
                    $scheme = $https ? 'https' : 'http';
                    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                    $dir = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
                    if ($dir === '/' || $dir === '.' || $dir === '\\') {
                        $dir = '';
                    }
                    $resolved = $scheme . '://' . $host . rtrim($dir, '/');
                }
            }
            $base = rtrim($resolved, '/');
        }
        if ($path === '') {
            return $base . '/';
        }
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('old')) {
    function old(string $key, $default = '')
    {
        $old = \App\Core\Session::get('_old', []);
        return $old[$key] ?? $default;
    }
}

if (!function_exists('error_of')) {
    function error_of(string $key): ?string
    {
        $errors = \App\Core\Session::get('_errors', []);
        return $errors[$key] ?? null;
    }
}

if (!function_exists('money')) {
    function money($amount, string $symbol = '₺', int $decimals = 2): string
    {
        $formatted = number_format((float) $amount, $decimals, ',', '.');
        return $symbol . $formatted;
    }
}

if (!function_exists('money_plain')) {
    function money_plain($amount, int $decimals = 2): string
    {
        return number_format((float) $amount, $decimals, ',', '.');
    }
}

if (!function_exists('flash')) {
    function flash(string $type, string $message): void
    {
        \App\Core\Session::flash($type, $message);
    }
}

if (!function_exists('old_input')) {
    function old_input(array $data): void
    {
        $keep = [];
        foreach ($data as $key => $value) {
            if ($key === '_token' || is_array($value)) {
                continue;
            }
            $keep[$key] = $value;
        }
        \App\Core\Session::set('_old', $keep);
    }
}

if (!function_exists('order_status_label')) {
    function order_status_label(string $status): string
    {
        return [
            'pending_company_approval' => 'Şirket Onayı Bekliyor',
            'pending_admin_approval'   => 'Finans Onayı Bekliyor',
            'approved'                 => 'Onaylandı',
            'preparing'                => 'Hazırlanıyor',
            'partially_shipped'        => 'Kısmi Sevkiyat',
            'shipped'                  => 'Sevk Edildi',
            'delivered'                => 'Teslim Edildi',
            'cancelled'                => 'İptal Edildi',
            'rejected'                 => 'Reddedildi',
        ][$status] ?? $status;
    }
}

if (!function_exists('order_status_classes')) {
    function order_status_classes(string $status): string
    {
        return [
            'pending_company_approval' => 'bg-amber-50 text-amber-700 border-amber-200',
            'pending_admin_approval'   => 'bg-red-50 text-red-700 border-red-200',
            'approved'                 => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'preparing'                => 'bg-blue-50 text-blue-700 border-blue-200',
            'partially_shipped'        => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'shipped'                  => 'bg-sky-50 text-sky-700 border-sky-200',
            'delivered'                => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'cancelled'                => 'bg-slate-100 text-slate-600 border-slate-200',
            'rejected'                 => 'bg-red-50 text-red-700 border-red-200',
        ][$status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    }
}

if (!function_exists('tier_label')) {
    function tier_label(string $tier): string
    {
        return ['bronze' => 'Bronze', 'silver' => 'Silver', 'gold' => 'Gold', 'platinum' => 'Platinum', 'vip' => 'VIP'][$tier] ?? ucfirst($tier);
    }
}

if (!function_exists('role_label')) {
    function role_label(string $role): string
    {
        return [
            'super_admin' => 'Sistem Yöneticisi',
            'sales_manager' => 'Satış Yöneticisi',
            'finance_admin' => 'Finans Yöneticisi',
            'company_owner' => 'Firma Sahibi',
            'purchasing_manager' => 'Satın Alma Müdürü',
            'buyer' => 'Satın Alma Personeli',
            'accountant' => 'Muhasebeci',
        ][$role] ?? $role;
    }
}

if (!function_exists('payment_method_label')) {
    function payment_method_label(string $method): string
    {
        return [
            'open_account' => 'Açık Hesap (Vadeli)',
            'bank_transfer' => 'Havale / EFT',
            'credit_card' => 'Kredi Kartı',
            'cheque' => 'Çek / Senet',
        ][$method] ?? $method;
    }
}

if (!function_exists('quote_status_label')) {
    function quote_status_label(string $status): string
    {
        return [
            'requested' => 'Talep Alındı',
            'reviewing' => 'İnceleniyor',
            'offered'   => 'Teklif Verildi',
            'accepted'  => 'Kabul Edildi',
            'rejected'  => 'Reddedildi',
            'expired'   => 'Süresi Doldu',
        ][$status] ?? $status;
    }
}

if (!function_exists('quote_status_classes')) {
    function quote_status_classes(string $status): string
    {
        return [
            'requested' => 'bg-amber-50 text-amber-700 border-amber-200',
            'reviewing' => 'bg-blue-50 text-blue-700 border-blue-200',
            'offered'   => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'accepted'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'rejected'  => 'bg-red-50 text-red-700 border-red-200',
            'expired'   => 'bg-slate-100 text-slate-600 border-slate-200',
        ][$status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
    }
}

if (!function_exists('tr_date')) {
    function tr_date(?string $date, bool $withTime = false): string
    {
        if ($date === null || $date === '') {
            return '-';
        }
        $ts = strtotime($date);
        if ($ts === false) {
            return '-';
        }
        $months = ['', 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
        $out = date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
        return $withTime ? $out . ' ' . date('H:i', $ts) : $out;
    }
}
