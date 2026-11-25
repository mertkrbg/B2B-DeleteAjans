<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Class CreditRiskService
 *
 * B2B Cari Risk ve Limit Değerlendirme Servisi.
 * Kredi limiti, açık hesap bakiyesi, bekleyen sipariş hacmi
 * ve FIFO tabanlı vade yaşlandırma (aging) analizlerini hesaplar.
 *
 * @package App\Services
 */
class CreditRiskService
{
    private const OPEN_STATUSES = "'pending_company_approval','pending_admin_approval','approved','preparing','partially_shipped','shipped'";

    /**
     * Cari hareketleri FIFO (en eski faturadan) mahsup ederek vade yaşlandırma üretir.
     * @return array{not_due:float,d0_30:float,d31_60:float,d61_90:float,d90_plus:float,overdue:float,open_total:float}
     */
    public static function aging(int $companyId): array
    {
        $rows = Database::fetchAll(
            "SELECT debit_amount, credit_amount, due_date, created_at
             FROM current_account_transactions
             WHERE company_id = :co AND status = 'confirmed'
             ORDER BY created_at ASC, id ASC",
            [':co' => $companyId]
        );

        $invoices = [];
        foreach ($rows as $row) {
            $debit = (float) $row['debit_amount'];
            $credit = (float) $row['credit_amount'];
            if ($debit > 0) {
                $dueTs = $row['due_date'] !== null ? strtotime((string) $row['due_date']) : strtotime((string) $row['created_at']);
                $invoices[] = ['due' => $dueTs !== false ? $dueTs : time(), 'remaining' => $debit];
            }
            if ($credit > 0) {
                $toApply = $credit;
                foreach ($invoices as &$inv) {
                    if ($toApply <= 0.00001) {
                        break;
                    }
                    if ($inv['remaining'] <= 0.00001) {
                        continue;
                    }
                    $apply = min($inv['remaining'], $toApply);
                    $inv['remaining'] -= $apply;
                    $toApply -= $apply;
                }
                unset($inv);
            }
        }

        $today = strtotime(date('Y-m-d'));
        $buckets = ['not_due' => 0.0, 'd0_30' => 0.0, 'd31_60' => 0.0, 'd61_90' => 0.0, 'd90_plus' => 0.0];
        foreach ($invoices as $inv) {
            $amount = $inv['remaining'];
            if ($amount <= 0.00001) {
                continue;
            }
            $days = (int) floor(($today - $inv['due']) / 86400);
            if ($days <= 0) {
                $buckets['not_due'] += $amount;
            } elseif ($days <= 30) {
                $buckets['d0_30'] += $amount;
            } elseif ($days <= 60) {
                $buckets['d31_60'] += $amount;
            } elseif ($days <= 90) {
                $buckets['d61_90'] += $amount;
            } else {
                $buckets['d90_plus'] += $amount;
            }
        }

        $buckets = array_map(static fn ($v) => round($v, 4), $buckets);
        $buckets['overdue'] = round($buckets['d0_30'] + $buckets['d31_60'] + $buckets['d61_90'] + $buckets['d90_plus'], 4);
        $buckets['open_total'] = round(
            $buckets['not_due'] + $buckets['d0_30'] + $buckets['d31_60'] + $buckets['d61_90'] + $buckets['d90_plus'],
            4
        );
        return $buckets;
    }

    /**
     * Bayi risk limiti anlık özeti. Tüm parasal hesaplar bcmath ile yapılır.
     */
    public static function snapshot(int $companyId): array
    {
        $company = Database::fetch(
            'SELECT credit_limit, current_balance, overdue_balance, payment_term_days
             FROM companies WHERE id = :id LIMIT 1',
            [':id' => $companyId]
        );

        if ($company === null) {
            return [
                'limit' => 0.0, 'balance' => 0.0, 'overdue' => 0.0,
                'open_orders' => 0.0, 'available' => 0.0, 'term_days' => 0,
                'has_overdue' => false, 'over_limit' => false,
            ];
        }

        // Faturalanmamış (cari bakiyeye henüz yansımamış) açık siparişler.
        $openOrders = (string) Database::fetchColumn(
            'SELECT COALESCE(SUM(grand_total), 0) FROM orders
             WHERE company_id = :id AND status IN (' . self::OPEN_STATUSES . ') AND invoice_number IS NULL',
            [':id' => $companyId]
        );

        $aging = self::aging($companyId);
        $overdue = (string) $aging['overdue'];

        $limit = (string) $company['credit_limit'];
        $balance = (string) $company['current_balance'];

        $available = bcsub(bcsub($limit, $balance, 4), $openOrders, 4);
        $overdueFlag = bccomp($overdue, '0', 4) === 1;

        return [
            'limit'       => (float) $limit,
            'balance'     => (float) $balance,
            'overdue'     => (float) $overdue,
            'open_orders' => (float) $openOrders,
            'available'   => (float) $available,
            'term_days'   => (int) $company['payment_term_days'],
            'has_overdue' => $overdueFlag,
            'over_limit'  => bccomp($available, '0', 4) === -1,
        ];
    }

    public static function canAfford(int $companyId, string $amount): bool
    {
        $snapshot = self::snapshot($companyId);
        if ($snapshot['has_overdue']) {
            return false;
        }
        return bccomp((string) $snapshot['available'], $amount, 4) >= 0;
    }
}
