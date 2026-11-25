<?php declare(strict_types=1);

return [
    'orders' => [
        ['company' => 'anadolu', 'buyer' => 'bayi@anadoluelektrik.test', 'items' => [['B2B-ELK-1001', 200], ['B2B-LGT-2001', 24]], 'status' => 'delivered', 'method' => 'open_account', 'term' => 45, 'days_ago' => 62, 'po' => 'PO-2026-0041', 'cargo' => 'Aras Kargo', 'tracking' => 'ARS-77810234', 'invoice' => 'FTR-2026-0031'],
        ['company' => 'anadolu', 'buyer' => 'bayi@anadoluelektrik.test', 'items' => [['B2B-OTM-3001', 20], ['B2B-OTM-3003', 12], ['B2B-ELK-1003', 6]], 'status' => 'shipped', 'method' => 'open_account', 'term' => 45, 'days_ago' => 12, 'po' => 'PO-2026-0058', 'cargo' => 'Yurtiçi Kargo', 'tracking' => 'YK-90125510', 'invoice' => 'FTR-2026-0044'],
        ['company' => 'anadolu', 'buyer' => 'bayi@anadoluelektrik.test', 'items' => [['B2B-HRD-4001', 15], ['B2B-HRD-4006', 20]], 'status' => 'approved', 'method' => 'bank_transfer', 'term' => 0, 'days_ago' => 4, 'po' => 'PO-2026-0063', 'cargo' => null, 'tracking' => null, 'invoice' => null],
        ['company' => 'anadolu', 'buyer' => 'satinalma@anadoluelektrik.test', 'items' => [['B2B-LGT-2002', 6], ['B2B-OTM-3004', 2]], 'status' => 'pending_company_approval', 'method' => 'open_account', 'term' => 45, 'days_ago' => 1, 'po' => 'PO-2026-0067', 'cargo' => null, 'tracking' => null, 'invoice' => null],
        ['company' => 'marmara', 'buyer' => 'sahibi@marmarayapi.test', 'items' => [['B2B-ELK-1002', 300], ['B2B-LGT-2004', 18]], 'status' => 'delivered', 'method' => 'open_account', 'term' => 30, 'days_ago' => 40, 'po' => 'MRM-1120', 'cargo' => 'Sürat Kargo', 'tracking' => 'SRT-44102281', 'invoice' => 'FTR-2026-0025'],
        ['company' => 'marmara', 'buyer' => 'sahibi@marmarayapi.test', 'items' => [['B2B-OTM-3002', 10]], 'status' => 'pending_admin_approval', 'method' => 'open_account', 'term' => 30, 'days_ago' => 2, 'po' => 'MRM-1155', 'cargo' => null, 'tracking' => null, 'invoice' => null],
        ['company' => 'baskent', 'buyer' => 'sahibi@baskentendustri.test', 'items' => [['B2B-LGT-2005', 8], ['B2B-ELK-1004', 10], ['B2B-OTM-3006', 30]], 'status' => 'delivered', 'method' => 'cheque', 'term' => 60, 'days_ago' => 55, 'po' => 'BSK-2026-77', 'cargo' => 'MNG Kargo', 'tracking' => 'MNG-55912044', 'invoice' => 'FTR-2026-0018'],
        ['company' => 'baskent', 'buyer' => 'sahibi@baskentendustri.test', 'items' => [['B2B-HRD-4002', 40], ['B2B-HRD-4003', 25]], 'status' => 'preparing', 'method' => 'open_account', 'term' => 60, 'days_ago' => 3, 'po' => 'BSK-2026-81', 'cargo' => null, 'tracking' => null, 'invoice' => null],
    ],
    'quotes' => [
        ['company' => 'anadolu', 'user' => 'bayi@anadoluelektrik.test', 'status' => 'offered', 'valid_days' => 14, 'customer_note' => 'Depo aydınlatma projesi için toplu alım teklifi talep ediyoruz.', 'admin_note' => 'Proje bazlı ek iskonto uygulandı.', 'items' => [['B2B-LGT-2001', 120, 430.0, 415.0], ['B2B-LGT-2003', 60, 690.0, 672.0]]],
        ['company' => 'anadolu', 'user' => 'onay@anadoluelektrik.test', 'status' => 'requested', 'valid_days' => 7, 'customer_note' => 'Yıllık kablo ihtiyacımız için fiyat çalışması rica ederiz.', 'admin_note' => null, 'items' => [['B2B-ELK-1001', 1500, 39.5, null], ['B2B-ELK-1002', 1200, 30.0, null]]],
    ],
    'carts' => [
        ['company' => 'anadolu', 'user' => 'bayi@anadoluelektrik.test', 'name' => 'Aylık Sarf Malzeme Şablonu', 'is_template' => 1, 'items' => [['B2B-ELK-1006', 20], ['B2B-HRD-4006', 10], ['B2B-LGT-2006', 8]]],
    ],
    'current_accounts' => [
        'anadolu' => [
            ['invoice_debit', 'FTR-2026-0031 Nolu Satış Faturası', 120000, 0, 107, 45, 'order'],
            ['payment_credit', 'Havale/EFT Tahsilatı', 0, 75000, 30, null, 'payment'],
        ],
        'marmara' => [
            ['invoice_debit', 'FTR-2026-0025 Nolu Satış Faturası', 60000, 0, 40, 30, 'order'],
            ['payment_credit', 'Çek Tahsilatı', 0, 30000, 20, null, 'payment'],
        ],
        'baskent' => [
            ['invoice_debit', 'FTR-2026-0018 Nolu Satış Faturası', 300000, 0, 55, 5, 'order'],
            ['payment_credit', 'Havale/EFT Tahsilatı', 0, 180000, 25, null, 'payment'],
        ],
        'karadeniz' => [
            ['invoice_debit', 'FTR-2026-0009 Nolu Satış Faturası', 48000, 0, 75, 10, 'order'],
        ],
    ],
    'payment_notifications' => [
        ['company' => 'anadolu', 'amount' => 25000, 'method' => 'bank_transfer', 'bank' => 'Garanti BBVA', 'ref' => 'GRT-2026-88213', 'note' => 'FTR-2026-0044 kısmi ödeme dekontu.', 'status' => 'pending', 'days_ago' => 1],
    ],
    'notifications' => [
        ['company' => 'anadolu', 'user' => 'bayi@anadoluelektrik.test', 'title' => 'Teklifiniz Güncellendi', 'message' => 'TKL-2026-0001 numaralı teklif talebinize satış ekibimiz karşı teklif verdi.', 'type' => 'info', 'days_ago' => 2],
        ['company' => 'anadolu', 'user' => 'bayi@anadoluelektrik.test', 'title' => 'Siparişiniz Yola Çıktı', 'message' => 'SIP-2026-00002 numaralı siparişiniz kargoya teslim edildi.', 'type' => 'success', 'days_ago' => 1],
    ],
    'audit_logs' => [
        ['user' => 'admin@b2b.test', 'action' => 'company.approve', 'entity' => 'company', 'entity_id' => 1, 'desc' => 'Anadolu Elektrik bayisi onaylandı.', 'days_ago' => 90],
        ['user' => 'satis@b2b.test', 'action' => 'company.update_credit', 'entity' => 'company', 'entity_id' => 1, 'desc' => 'Risk limiti 250.000 TL olarak güncellendi.', 'days_ago' => 60],
        ['user' => 'finans@b2b.test', 'action' => 'payment.confirm', 'entity' => 'payment', 'entity_id' => 1, 'desc' => '75.000 TL havale tahsilatı onaylandı.', 'days_ago' => 30],
    ],
    'settings' => [
        ['company_title', 'B2B Enterprise Tedarik A.Ş.', 'general'],
        ['default_vat_rate', '20', 'tax'],
        ['shipping_cost', '250.00', 'shipping'],
        ['bank_iban', 'TR33 0006 1005 1978 6457 8413 26', 'payment'],
        ['bank_name', 'Garanti BBVA', 'payment'],
        ['min_order_note', 'Minimum sipariş tutarı 500 TL\'dir.', 'general'],
        ['support_email', 'destek@b2b.test', 'general'],
    ],
];
