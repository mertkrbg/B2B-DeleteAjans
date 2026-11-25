<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class AdminSettingsController
 *
 * Sistem Ayarları, Döviz Kurları ve Denetim Günlüğü Kontrolcüsü.
 * USD/EUR TCMB satış kurları güncelleme, firma genel ayarları, KDV/kargo oranları
 * ve güvenlik denetim kayıtlarını (Audit Logs) filtreleyip görüntüler.
 *
 * @package App\Controllers\Admin
 */
class AdminSettingsController extends AdminController
{
    private const SETTING_KEYS = [
        'company_title'     => 'general',
        'support_email'     => 'general',
        'min_order_note'    => 'general',
        'default_vat_rate'  => 'tax',
        'shipping_cost'     => 'shipping',
        'bank_name'         => 'payment',
        'bank_iban'         => 'payment',
    ];

    public function index(Request $request): Response
    {
        $rows = Database::fetchAll('SELECT setting_key, setting_value, group_name FROM system_settings');
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = (string) $row['setting_value'];
        }
        foreach (self::SETTING_KEYS as $key => $group) {
            if (!array_key_exists($key, $settings)) {
                $settings[$key] = '';
            }
        }

        $rates = Database::fetchAll('SELECT * FROM currency_rates ORDER BY currency_code');

        $action = trim((string) $request->query('islem', ''));
        $entity = trim((string) $request->query('varlik', ''));
        $page = max(1, (int) $request->query('sayfa', 1));
        $perPage = 50;
        $where = '1=1';
        $params = [];
        if ($action !== '') {
            $where .= ' AND a.action LIKE :act';
            $params[':act'] = '%' . $action . '%';
        }
        if ($entity !== '') {
            $where .= ' AND a.entity_type = :ent';
            $params[':ent'] = $entity;
        }
        $total = (int) Database::fetchColumn("SELECT COUNT(*) FROM audit_logs a WHERE $where", $params);
        $offset = ($page - 1) * $perPage;
        $logs = Database::fetchAll(
            "SELECT a.*, u.full_name AS user_name, c.title AS company_title
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN companies c ON c.id = a.company_id
             WHERE $where
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT $perPage OFFSET $offset",
            $params
        );
        $entities = Database::fetchAll("SELECT DISTINCT entity_type FROM audit_logs WHERE entity_type IS NOT NULL ORDER BY entity_type");

        return $this->renderAdmin('settings', [
            'title'      => 'Ayarlar & Denetim Logları',
            'settings'   => $settings,
            'rates'      => $rates,
            'logs'       => $logs,
            'entities'   => $entities,
            'actionFilter' => $action,
            'entityFilter' => $entity,
            'page'       => $page,
            'total'      => $total,
            'perPage'    => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ]);
    }

    public function updateRates(Request $request): Response
    {
        $input = (array) $request->input('rates', []);
        $existing = Database::fetchAll('SELECT currency_code FROM currency_rates');
        $validCodes = array_map(static fn (array $r): string => (string) $r['currency_code'], $existing);
        $changes = [];

        foreach ($input as $code => $values) {
            $code = strtoupper((string) $code);
            if (!in_array($code, $validCodes, true) || !is_array($values)) {
                continue;
            }
            $buying = trim((string) ($values['buying'] ?? ''));
            $selling = trim((string) ($values['selling'] ?? ''));
            if (!is_numeric($buying) || !is_numeric($selling) || (float) $buying <= 0 || (float) $selling <= 0) {
                Session::flash('error', $code . ' için geçerli pozitif kur değerleri giriniz.');
                return Response::redirect(url('admin/ayarlar'));
            }
            $old = Database::fetch('SELECT buying_rate, selling_rate FROM currency_rates WHERE currency_code = :c', [':c' => $code]);
            Database::update('currency_rates', [
                'buying_rate'  => bcadd($buying, '0', 4),
                'selling_rate' => bcadd($selling, '0', 4),
                'updated_at'   => date('Y-m-d H:i:s'),
            ], 'currency_code = :c', [':c' => $code]);
            $changes[$code] = [
                'old' => ['buying' => (string) ($old['buying_rate'] ?? ''), 'selling' => (string) ($old['selling_rate'] ?? '')],
                'new' => ['buying' => bcadd($buying, '0', 4), 'selling' => bcadd($selling, '0', 4)],
            ];
        }

        if (empty($changes)) {
            Session::flash('warning', 'Güncellenecek kur bulunamadı.');
            return Response::redirect(url('admin/ayarlar'));
        }

        Database::insert('audit_logs', [
            'user_id'         => Session::authId(),
            'action'          => 'settings.currency_rates',
            'entity_type'     => 'currency_rates',
            'entity_id'       => null,
            'old_values_json' => json_encode(array_map(static fn ($c) => $c['old'], $changes), JSON_UNESCAPED_UNICODE),
            'new_values_json' => json_encode(array_map(static fn ($c) => $c['new'], $changes), JSON_UNESCAPED_UNICODE),
            'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Döviz kurları güncellendi. Ürün fiyatlarına anında yansıyacaktır.');
        return Response::redirect(url('admin/ayarlar'));
    }

    public function updateSettings(Request $request): Response
    {
        $oldValues = [];
        $newValues = [];
        foreach (self::SETTING_KEYS as $key => $group) {
            if (!$request->has($key)) {
                continue;
            }
            $value = trim((string) $request->input($key, ''));
            $old = Database::fetch('SELECT setting_value FROM system_settings WHERE setting_key = :k', [':k' => $key]);
            $oldValue = $old !== null ? (string) $old['setting_value'] : null;
            if ($oldValue === $value) {
                continue;
            }
            Database::query(
                'INSERT INTO system_settings (setting_key, setting_value, group_name, updated_at)
                 VALUES (:k, :v, :g, :u)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), group_name = VALUES(group_name), updated_at = VALUES(updated_at)',
                [':k' => $key, ':v' => $value, ':g' => $group, ':u' => date('Y-m-d H:i:s')]
            );
            $oldValues[$key] = $oldValue;
            $newValues[$key] = $value;
        }

        if (empty($newValues)) {
            Session::flash('info', 'Değişiklik yapılmadı.');
            return Response::redirect(url('admin/ayarlar'));
        }

        Database::insert('audit_logs', [
            'user_id'         => Session::authId(),
            'action'          => 'settings.update',
            'entity_type'     => 'system_settings',
            'entity_id'       => null,
            'old_values_json' => json_encode($oldValues, JSON_UNESCAPED_UNICODE),
            'new_values_json' => json_encode($newValues, JSON_UNESCAPED_UNICODE),
            'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Sistem ayarları güncellendi.');
        return Response::redirect(url('admin/ayarlar'));
    }
}
