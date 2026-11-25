<?php declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Class InvoicePdfService
 *
 * Proforma Fatura ve Sipariş Makbuzu Çıktı Servisi.
 * Firma bilgileri, sevkiyat/fatura adresleri, KDV dökümü ve banka IBAN
 * verilerini derleyerek yazdırma/PDF formatına hazır hale getirir.
 *
 * @package App\Services
 */
class InvoicePdfService
{
    /**
     * Yazdırılabilir Proforma / Sipariş belgesi için veri kümesi.
     */
    public static function orderDocument(int $companyId, string $orderNumber): ?array
    {
        $order = Database::fetch(
            'SELECT * FROM orders WHERE order_number = :n AND company_id = :co LIMIT 1',
            [':n' => $orderNumber, ':co' => $companyId]
        );
        if ($order === null) {
            return null;
        }
        $company = Database::fetch('SELECT * FROM companies WHERE id = :id', [':id' => $companyId]);
        $items = Database::fetchAll('SELECT * FROM order_items WHERE order_id = :id ORDER BY id ASC', [':id' => $order['id']]);
        $shipping = Database::fetch('SELECT * FROM company_addresses WHERE id = :id', [':id' => $order['shipping_address_id']]);
        $billing = Database::fetch('SELECT * FROM company_addresses WHERE id = :id', [':id' => $order['billing_address_id']]);
        $seller = [
            'title'    => (string) (Database::fetchColumn("SELECT setting_value FROM system_settings WHERE setting_key = 'company_title'") ?: 'B2B Enterprise Tedarik A.Ş.'),
            'phone'    => '0850 000 00 00',
            'email'    => (string) (Database::fetchColumn("SELECT setting_value FROM system_settings WHERE setting_key = 'support_email'") ?: 'destek@b2b.test'),
            'address'  => 'Organize Sanayi Bölgesi 1. Cadde No:1, Başakşehir / İstanbul',
            'tax_office' => 'Başakşehir',
            'tax_number' => '9876543210',
            'iban'     => (string) (Database::fetchColumn("SELECT setting_value FROM system_settings WHERE setting_key = 'bank_iban'") ?: '-'),
            'bank'     => (string) (Database::fetchColumn("SELECT setting_value FROM system_settings WHERE setting_key = 'bank_name'") ?: '-'),
        ];
        return [
            'order'    => $order,
            'company'  => $company,
            'items'    => $items,
            'shipping' => $shipping,
            'billing'  => $billing,
            'seller'   => $seller,
        ];
    }
}
