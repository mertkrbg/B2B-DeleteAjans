<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CreditRiskService;
use App\Services\FileUploader;

/**
 * Class FinanceController
 *
 * B2B Cari Hesap ve Finans Yönetimi Kontrolcüsü.
 * Cari ekstre dökümü (PDF/Excel), vade yaşlandırma (aging) raporu,
 * havale/EFT dekont yükleme ve ödeme bildirimi takibini yönetir.
 *
 * @package App\Controllers
 */
class FinanceController extends PortalController
{
    private const FINANCE_ROLES = ['company_owner', 'accountant', 'purchasing_manager'];

    private function guard(): ?Response
    {
        if (!in_array((string) Session::get('role', ''), self::FINANCE_ROLES, true)) {
            Session::flash('error', 'Cari hesap görüntüleme yetkiniz bulunmuyor.');
            return Response::redirect(url('portal'));
        }
        return null;
    }

    private function filteredTransactions(array $filters): array
    {
        $where = 'company_id = :co';
        $params = [':co' => $this->companyId()];
        if (!empty($filters['from'])) {
            $where .= ' AND DATE(created_at) >= :from';
            $params[':from'] = $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where .= ' AND DATE(created_at) <= :to';
            $params[':to'] = $filters['to'];
        }
        return Database::fetchAll(
            "SELECT * FROM current_account_transactions WHERE $where ORDER BY created_at ASC, id ASC",
            $params
        );
    }

    private function aging(): array
    {
        return CreditRiskService::aging($this->companyId());
    }

    private function payments(): array
    {
        return Database::fetchAll(
            'SELECT * FROM payment_notifications WHERE company_id = :co ORDER BY created_at DESC, id DESC',
            [':co' => $this->companyId()]
        );
    }

    private function filters(Request $request): array
    {
        return [
            'from' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('baslangic', '')) ? (string) $request->query('baslangic') : '',
            'to'   => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $request->query('bitis', '')) ? (string) $request->query('bitis') : '',
        ];
    }

    public function index(Request $request): Response
    {
        if ($response = $this->guard()) {
            return $response;
        }
        $filters = $this->filters($request);
        return $this->renderPortal('finance', [
            'title'        => 'Cari Hesap & Faturalar',
            'transactions' => $this->filteredTransactions($filters),
            'aging'        => $this->aging(),
            'payments'     => $this->payments(),
            'filters'      => $filters,
        ]);
    }

    public function exportCsv(Request $request): Response
    {
        if ($response = $this->guard()) {
            return $response;
        }
        $filters = $this->filters($request);
        $rows = $this->filteredTransactions($filters);
        $lines = ["İşlem No;Tarih;Açıklama;Borç;Alacak;Bakiye;Vade;Durum"];
        foreach ($rows as $row) {
            $lines[] = implode(';', [
                $row['transaction_no'],
                date('d.m.Y', strtotime((string) $row['created_at'])),
                '"' . str_replace('"', '""', (string) $row['description']) . '"',
                number_format((float) $row['debit_amount'], 2, ',', ''),
                number_format((float) $row['credit_amount'], 2, ',', ''),
                number_format((float) $row['running_balance'], 2, ',', ''),
                $row['due_date'] !== null ? date('d.m.Y', strtotime((string) $row['due_date'])) : '',
                (string) $row['status'],
            ]);
        }
        return Response::download("\xEF\xBB\xBF" . implode("\r\n", $lines), 'cari-ekstre-' . date('Ymd') . '.csv');
    }

    public function printStatement(Request $request): Response
    {
        if ($response = $this->guard()) {
            return $response;
        }
        $filters = $this->filters($request);
        $html = View::render('print.statement', [
            'title'        => 'Cari Hesap Ekstresi',
            'company'      => $this->company(),
            'transactions' => $this->filteredTransactions($filters),
            'aging'        => $this->aging(),
            'filters'      => $filters,
        ], 'print');
        return Response::html($html);
    }

    public function notifyPayment(Request $request): Response
    {
        if ($response = $this->guard()) {
            return $response;
        }
        $amount = (float) $request->input('amount', 0);
        $method = $request->string('payment_method');
        $allowed = ['bank_transfer', 'credit_card', 'cheque', 'other'];
        if ($amount <= 0) {
            Session::flash('error', 'Geçerli bir ödeme tutarı giriniz.');
            return Response::redirect(url('portal/cari-hesap'));
        }
        if (!in_array($method, $allowed, true)) {
            $method = 'bank_transfer';
        }
        $receiptPath = null;
        $file = $request->file('receipt');
        if ($file !== null) {
            try {
                $receiptPath = FileUploader::store($file, 'receipts');
            } catch (\RuntimeException $e) {
                Session::flash('error', $e->getMessage());
                return Response::redirect(url('portal/cari-hesap'));
            }
        }
        Database::insert('payment_notifications', [
            'company_id' => $this->companyId(),
            'amount' => round($amount, 4),
            'payment_method' => $method,
            'bank_name' => $request->string('bank_name') !== '' ? $request->string('bank_name') : null,
            'reference_no' => $request->string('reference_no') !== '' ? $request->string('reference_no') : null,
            'receipt_file' => $receiptPath,
            'note' => $request->string('note') !== '' ? $request->string('note') : null,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        Session::flash('success', 'Ödeme bildiriminiz alındı. Finans ekibi onayladıktan sonra cari bakiyenize yansıyacaktır.');
        return Response::redirect(url('portal/cari-hesap'));
    }
}
