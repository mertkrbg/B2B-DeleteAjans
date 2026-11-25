<?php declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * Class AdminFinanceController
 *
 * ERP Finans ve Tahsilat Yönetimi Kontrolcüsü.
 * Havale/EFT bildirimlerini onaylama/reddetme, cari hesaba tahsilat işleme (bcmath),
 * güvenli dekont önizleme ve cari risk raporlamalarını yürütür.
 *
 * @package App\Controllers\Admin
 */
class AdminFinanceController extends AdminController
{
    public function index(Request $request): Response
    {
        $status = trim((string) $request->query('durum', ''));
        $where = '1=1';
        $params = [];
        if ($status !== '' && in_array($status, ['pending', 'confirmed', 'rejected'], true)) {
            $where .= ' AND pn.status = :st';
            $params[':st'] = $status;
        }
        $payments = Database::fetchAll(
            "SELECT pn.*, c.title AS company_title, c.company_code, u.full_name AS reviewer_name
             FROM payment_notifications pn
             JOIN companies c ON c.id = pn.company_id
             LEFT JOIN users u ON u.id = pn.reviewed_by
             WHERE $where
             ORDER BY (pn.status = 'pending') DESC, pn.created_at DESC, pn.id DESC LIMIT 200",
            $params
        );

        $countRaw = Database::fetchAll('SELECT status, COUNT(*) AS c FROM payment_notifications GROUP BY status');
        $counts = ['all' => 0, 'pending' => 0, 'confirmed' => 0, 'rejected' => 0];
        foreach ($countRaw as $row) {
            $counts[$row['status']] = (int) $row['c'];
            $counts['all'] += (int) $row['c'];
        }

        $kpi = Database::fetch(
            "SELECT
                COALESCE(SUM(CASE WHEN status = 'active' THEN current_balance ELSE 0 END), 0) AS total_receivable,
                COALESCE(SUM(CASE WHEN status = 'active' THEN overdue_balance ELSE 0 END), 0) AS total_overdue,
                COALESCE(SUM(CASE WHEN status = 'active' THEN credit_limit ELSE 0 END), 0) AS total_limit
             FROM companies"
        ) ?? ['total_receivable' => 0, 'total_overdue' => 0, 'total_limit' => 0];

        $collectedThisMonth = (string) Database::fetchColumn(
            "SELECT COALESCE(SUM(credit_amount), 0) FROM current_account_transactions
             WHERE type IN ('payment_credit','cheque_receipt') AND status = 'confirmed'
               AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())"
        );

        $companies = Database::fetchAll(
            "SELECT id, company_code, title, tier_level, credit_limit, current_balance, overdue_balance, payment_term_days, status
             FROM companies
             WHERE status IN ('active','suspended')
             ORDER BY (overdue_balance > 0) DESC, current_balance DESC"
        );
        foreach ($companies as &$company) {
            $company['available_limit'] = bcsub(
                (string) $company['credit_limit'],
                bcadd((string) $company['current_balance'], (string) $company['overdue_balance'], 4),
                4
            );
        }
        unset($company);

        $recentTransactions = Database::fetchAll(
            "SELECT t.*, c.title AS company_title
             FROM current_account_transactions t JOIN companies c ON c.id = t.company_id
             ORDER BY t.created_at DESC, t.id DESC LIMIT 15"
        );

        return $this->renderAdmin('finance', [
            'title'            => 'Finans & Tahsilat',
            'payments'         => $payments,
            'statusFilter'     => $status,
            'counts'           => $counts,
            'kpi'              => $kpi,
            'collectedThisMonth' => $collectedThisMonth,
            'companies'        => $companies,
            'recentTransactions' => $recentTransactions,
        ]);
    }

    public function confirm(Request $request, string $id): Response
    {
        $payment = $this->findPayment($id);
        if ($payment === null) {
            Session::flash('error', 'Ödeme bildirimi bulunamadı.');
            return Response::redirect(url('admin/finans'));
        }
        if ($payment['status'] !== 'pending') {
            Session::flash('warning', 'Bu bildirim daha önce işlenmiş.');
            return Response::redirect(url('admin/finans'));
        }
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id', [':id' => (int) $payment['company_id']]);
        if ($company === null) {
            Session::flash('error', 'Bayi bulunamadı.');
            return Response::redirect(url('admin/finans'));
        }
        $amount = (string) $payment['amount'];
        try {
            Database::transaction(function () use ($payment, $company, $amount): void {
                $newBalance = bcsub((string) $company['current_balance'], $amount, 4);
                $type = $payment['payment_method'] === 'cheque' ? 'cheque_receipt' : 'payment_credit';
                Database::insert('current_account_transactions', [
                    'company_id'      => (int) $payment['company_id'],
                    'transaction_no'  => $this->generateTransactionNo(),
                    'type'            => $type,
                    'reference_type'  => 'payment',
                    'reference_id'    => (int) $payment['id'],
                    'description'     => 'Tahsilat Onayı - ' . ($payment['reference_no'] !== null ? $payment['reference_no'] : 'Dekont #' . $payment['id']),
                    'debit_amount'    => 0,
                    'credit_amount'   => $amount,
                    'running_balance' => $newBalance,
                    'due_date'        => null,
                    'status'          => 'confirmed',
                    'created_by'      => Session::authId(),
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                Database::update('companies', [
                    'current_balance' => $newBalance,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ], 'id = :id', [':id' => (int) $payment['company_id']]);
                Database::update('payment_notifications', [
                    'status'      => 'confirmed',
                    'reviewed_by' => Session::authId(),
                    'reviewed_at' => date('Y-m-d H:i:s'),
                ], 'id = :id', [':id' => (int) $payment['id']]);
                Database::insert('audit_logs', [
                    'user_id'         => Session::authId(),
                    'company_id'      => (int) $payment['company_id'],
                    'action'          => 'payment.confirm',
                    'entity_type'     => 'payment_notification',
                    'entity_id'       => (int) $payment['id'],
                    'old_values_json' => json_encode(['status' => 'pending'], JSON_UNESCAPED_UNICODE),
                    'new_values_json' => json_encode(['status' => 'confirmed', 'amount' => $amount, 'new_balance' => $newBalance], JSON_UNESCAPED_UNICODE),
                    'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            });
        } catch (\Throwable $e) {
            Session::flash('error', 'Tahsilat işlenirken hata oluştu.');
            return Response::redirect(url('admin/finans'));
        }
        Session::flash('success', 'Ödeme onaylandı ve cari bakiyeden düşüldü.');
        return Response::redirect(url('admin/finans'));
    }

    public function reject(Request $request, string $id): Response
    {
        $payment = $this->findPayment($id);
        if ($payment === null) {
            Session::flash('error', 'Ödeme bildirimi bulunamadı.');
            return Response::redirect(url('admin/finans'));
        }
        if ($payment['status'] !== 'pending') {
            Session::flash('warning', 'Bu bildirim daha önce işlenmiş.');
            return Response::redirect(url('admin/finans'));
        }
        Database::update('payment_notifications', [
            'status'      => 'rejected',
            'reviewed_by' => Session::authId(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ], 'id = :id', [':id' => (int) $payment['id']]);
        Database::insert('audit_logs', [
            'user_id'         => Session::authId(),
            'company_id'      => (int) $payment['company_id'],
            'action'          => 'payment.reject',
            'entity_type'     => 'payment_notification',
            'entity_id'       => (int) $payment['id'],
            'old_values_json' => json_encode(['status' => 'pending'], JSON_UNESCAPED_UNICODE),
            'new_values_json' => json_encode(['status' => 'rejected'], JSON_UNESCAPED_UNICODE),
            'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'created_at'      => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Ödeme bildirimi reddedildi.');
        return Response::redirect(url('admin/finans'));
    }

    public function manual(Request $request): Response
    {
        $companyId = (int) $request->input('company_id', 0);
        $amount = trim((string) $request->input('amount', ''));
        $method = $request->string('payment_method', 'bank_transfer');
        $description = trim((string) $request->input('description', ''));
        $reference = trim((string) $request->input('reference_no', ''));

        $company = $companyId > 0 ? Database::fetch('SELECT * FROM companies WHERE id = :id', [':id' => $companyId]) : null;
        if ($company === null) {
            Session::flash('error', 'Geçerli bir bayi seçiniz.');
            return Response::redirect(url('admin/finans'));
        }
        if (!is_numeric($amount) || (float) $amount <= 0) {
            Session::flash('error', 'Geçerli bir tahsilat tutarı giriniz.');
            return Response::redirect(url('admin/finans'));
        }
        $allowed = ['bank_transfer', 'credit_card', 'cheque', 'other'];
        if (!in_array($method, $allowed, true)) {
            $method = 'bank_transfer';
        }
        $amount = bcadd($amount, '0', 4);
        $description = $description !== '' ? $description : 'Manuel tahsilat' . ($reference !== '' ? ' (' . $reference . ')' : '');
        $methodLabels = ['bank_transfer' => 'Havale/EFT', 'credit_card' => 'Kredi Kartı', 'cheque' => 'Çek/Senet', 'other' => 'Diğer'];

        try {
            Database::transaction(function () use ($company, $amount, $method, $description, $reference, $methodLabels): void {
                $newBalance = bcsub((string) $company['current_balance'], $amount, 4);
                $type = $method === 'cheque' ? 'cheque_receipt' : 'payment_credit';
                Database::insert('current_account_transactions', [
                    'company_id'      => (int) $company['id'],
                    'transaction_no'  => $this->generateTransactionNo(),
                    'type'            => $type,
                    'reference_type'  => 'manual',
                    'reference_id'    => null,
                    'description'     => mb_substr($methodLabels[$method] . ' - ' . $description, 0, 250),
                    'debit_amount'    => 0,
                    'credit_amount'   => $amount,
                    'running_balance' => $newBalance,
                    'due_date'        => null,
                    'status'          => 'confirmed',
                    'created_by'      => Session::authId(),
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
                Database::update('companies', [
                    'current_balance' => $newBalance,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ], 'id = :id', [':id' => (int) $company['id']]);
                Database::insert('audit_logs', [
                    'user_id'         => Session::authId(),
                    'company_id'      => (int) $company['id'],
                    'action'          => 'payment.manual',
                    'entity_type'     => 'company',
                    'entity_id'       => (int) $company['id'],
                    'old_values_json' => json_encode(['current_balance' => (string) $company['current_balance']], JSON_UNESCAPED_UNICODE),
                    'new_values_json' => json_encode(['current_balance' => $newBalance, 'amount' => $amount, 'method' => $method, 'reference' => $reference], JSON_UNESCAPED_UNICODE),
                    'ip_address'      => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    'created_at'      => date('Y-m-d H:i:s'),
                ]);
            });
        } catch (\Throwable $e) {
            Session::flash('error', 'Manuel tahsilat kaydedilirken hata oluştu.');
            return Response::redirect(url('admin/finans'));
        }
        Session::flash('success', 'Manuel tahsilat kaydedildi ve cari bakiyeye işlendi.');
        return Response::redirect(url('admin/finans'));
    }

    public function receipt(Request $request, string $id): Response
    {
        $payment = Database::fetch('SELECT receipt_file FROM payment_notifications WHERE id = :id LIMIT 1', [':id' => (int) $id]);
        if ($payment === null || empty($payment['receipt_file'])) {
            Session::flash('error', 'Dekont dosyası bulunamadı.');
            return Response::redirect(url('admin/finans'));
        }
        $relative = (string) $payment['receipt_file'];
        if (str_contains($relative, '..')) {
            return Response::html('Geçersiz dosya.', 400);
        }
        $path = BASE_PATH . '/' . ltrim($relative, '/');
        if (!is_file($path)) {
            Session::flash('error', 'Dekont dosyası diskte bulunamadı.');
            return Response::redirect(url('admin/finans'));
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($path);
        $content = (string) file_get_contents($path);
        return new Response($content, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="dekont-' . (int) $id . '.' . pathinfo($path, PATHINFO_EXTENSION) . '"',
        ]);
    }

    private function findPayment(string $id): ?array
    {
        return Database::fetch('SELECT * FROM payment_notifications WHERE id = :id LIMIT 1', [':id' => (int) $id]);
    }

    private function generateTransactionNo(): string
    {
        $year = date('Y');
        $seq = (int) Database::fetchColumn(
            "SELECT COUNT(*) FROM current_account_transactions WHERE transaction_no LIKE :p",
            [':p' => 'TAH-' . $year . '-%']
        ) + 1;
        do {
            $candidate = sprintf('TAH-%s-%05d', $year, $seq);
            $exists = (int) Database::fetchColumn(
                'SELECT COUNT(*) FROM current_account_transactions WHERE transaction_no = :n',
                [':n' => $candidate]
            );
            $seq++;
        } while ($exists > 0);
        return $candidate;
    }
}
