<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CreditRiskService;

/**
 * Class AdminCompanyController
 *
 * Bayi Yönetimi Kontrolcüsü (Admin ERP).
 * Bayi başvurusu onay/red, vergi levhası görüntüleme, iskonto oranı ve fiyat listesi atama,
 * kredi ve risk limitleri tanımlama ve bayi detaylarını yönetir.
 *
 * @package App\Controllers\Admin
 */
class AdminCompanyController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->query('durum', ''));
        $q = trim((string) $request->query('q', ''));
        $where = '1=1';
        $params = [];
        if ($status !== '') {
            $where .= ' AND c.status = :st';
            $params[':st'] = $status;
        }
        if ($q !== '') {
            $where .= ' AND (c.title LIKE :q1 OR c.company_code LIKE :q2 OR c.tax_number LIKE :q3)';
            foreach ([':q1', ':q2', ':q3'] as $k) {
                $params[$k] = '%' . $q . '%';
            }
        }
        $companies = Database::fetchAll(
            "SELECT c.*, pl.name AS price_list_name
             FROM companies c LEFT JOIN price_lists pl ON pl.id = c.price_list_id
             WHERE $where ORDER BY (c.status = 'pending') DESC, c.id ASC",
            $params
        );
        $countsRaw = Database::fetchAll('SELECT status, COUNT(*) AS c FROM companies GROUP BY status');
        $counts = ['all' => 0];
        foreach ($countsRaw as $r) {
            $counts[$r['status']] = (int) $r['c'];
            $counts['all'] += (int) $r['c'];
        }

        return $this->renderAdmin('companies', [
            'title' => 'Bayi Yönetimi',
            'companies' => $companies,
            'statusFilter' => $status,
            'q' => $q,
            'statusCounts' => $counts,
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($company === null) {
            return Response::html(\App\Core\View::render('errors.404', ['title' => 'Bayi Bulunamadı'], 'auth'), 404);
        }
        $addresses = Database::fetchAll('SELECT * FROM company_addresses WHERE company_id = :id', [':id' => $company['id']]);
        $users = Database::fetchAll('SELECT * FROM users WHERE company_id = :id ORDER BY id', [':id' => $company['id']]);
        $orders = Database::fetchAll('SELECT * FROM orders WHERE company_id = :id ORDER BY created_at DESC LIMIT 8', [':id' => $company['id']]);
        $priceLists = Database::fetchAll('SELECT id, code, name FROM price_lists WHERE is_active = 1 ORDER BY name');
        $risk = CreditRiskService::snapshot((int) $company['id']);

        return $this->renderAdmin('company_detail', [
            'title' => 'Bayi: ' . $company['title'],
            'company' => $company,
            'addresses' => $addresses,
            'users' => $users,
            'orders' => $orders,
            'priceLists' => $priceLists,
            'risk' => $risk,
        ]);
    }

    public function update(Request $request, string $id): Response
    {
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($company === null) {
            Session::flash('error', 'Bayi bulunamadı.');
            return Response::redirect(url('admin/bayiler'));
        }
        $data = [
            'credit_limit' => (float) $request->input('credit_limit', $company['credit_limit']),
            'payment_term_days' => (int) $request->input('payment_term_days', $company['payment_term_days']),
            'tier_level' => $request->string('tier_level'),
            'base_discount_rate' => (float) $request->input('base_discount_rate', $company['base_discount_rate']),
            'price_list_id' => $request->int('price_list_id') > 0 ? $request->int('price_list_id') : null,
            'status' => $request->string('status'),
            'notes' => $request->string('notes'),
        ];
        if (!in_array($data['tier_level'], ['bronze', 'silver', 'gold', 'platinum', 'vip'], true)) {
            $data['tier_level'] = (string) $company['tier_level'];
        }
        if (!in_array($data['status'], ['pending', 'active', 'suspended', 'rejected'], true)) {
            $data['status'] = (string) $company['status'];
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        Database::update('companies', $data, 'id = :id', [':id' => $company['id']]);
        Database::insert('audit_logs', [
            'user_id' => Session::authId(), 'company_id' => (int) $company['id'], 'action' => 'company.update',
            'entity_type' => 'company', 'entity_id' => (int) $company['id'],
            'old_values_json' => json_encode(['credit_limit' => $company['credit_limit'], 'status' => $company['status'], 'tier_level' => $company['tier_level']], JSON_UNESCAPED_UNICODE),
            'new_values_json' => json_encode(['credit_limit' => $data['credit_limit'], 'status' => $data['status'], 'tier_level' => $data['tier_level']], JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Bayi bilgileri güncellendi.');
        return Response::redirect(url('admin/bayiler/' . $company['id']));
    }

    public function approve(Request $request, string $id): Response
    {
        return $this->setStatus((int) $id, 'active', 'Bayi onaylandı.');
    }

    public function reject(Request $request, string $id): Response
    {
        return $this->setStatus((int) $id, 'rejected', 'Bayi başvurusu reddedildi.');
    }

    private function setStatus(int $id, string $status, string $message): Response
    {
        $company = Database::fetch('SELECT id, status FROM companies WHERE id = :id LIMIT 1', [':id' => $id]);
        if ($company === null) {
            Session::flash('error', 'Bayi bulunamadı.');
            return Response::redirect(url('admin/bayiler'));
        }
        Database::update('companies', ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')], 'id = :id', [':id' => $id]);
        Database::insert('audit_logs', [
            'user_id' => Session::authId(), 'company_id' => $id, 'action' => 'company.' . $status,
            'entity_type' => 'company', 'entity_id' => $id,
            'old_values_json' => json_encode(['status' => $company['status']], JSON_UNESCAPED_UNICODE),
            'new_values_json' => json_encode(['status' => $status], JSON_UNESCAPED_UNICODE),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', $message);
        return Response::redirect(url('admin/bayiler/' . $id));
    }

    public function taxPlate(Request $request, string $id): Response
    {
        $company = Database::fetch('SELECT tax_plate_file FROM companies WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($company === null || empty($company['tax_plate_file'])) {
            Session::flash('error', 'Vergi levhası bulunamadı.');
            return Response::redirect(url('admin/bayiler/' . $id));
        }
        $relative = (string) $company['tax_plate_file'];
        if (str_contains($relative, '..')) {
            return Response::html('Geçersiz dosya.', 400);
        }
        $path = BASE_PATH . '/' . ltrim($relative, '/');
        if (!is_file($path)) {
            Session::flash('error', 'Vergi levhası dosyası diskte bulunamadı.');
            return Response::redirect(url('admin/bayiler/' . $id));
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
        $content = (string) file_get_contents($path);
        return new Response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="vergi-levhasi-' . (int) $id . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"',
        ]);
    }
}
