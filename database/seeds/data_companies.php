<?php declare(strict_types=1);

return [
    'companies' => [
        'anadolu' => [
            'code' => 'BY-2026-0001', 'title' => 'Anadolu Elektrik Malzemeleri San. ve Tic. A.Ş.',
            'brand' => 'Anadolu Elektrik', 'tax_office' => 'Beşiktaş', 'tax_number' => '1234567890',
            'tier' => 'gold', 'price_list' => 'PL-GOLD', 'discount' => 12.00,
            'credit' => 250000, 'balance' => 45000, 'overdue' => 0, 'term' => 45,
            'status' => 'active', 'city' => 'İstanbul', 'district' => 'Bağcılar',
            'email' => 'bayi@anadoluelektrik.test', 'sales_rep' => 'satis@b2b.test',
        ],
        'ege' => [
            'code' => 'BY-2026-0002', 'title' => 'Ege Otomasyon Sistemleri Ltd. Şti.',
            'brand' => 'Ege Otomasyon', 'tax_office' => 'Bornova', 'tax_number' => '2345678901',
            'tier' => 'bronze', 'price_list' => null, 'discount' => 0.00,
            'credit' => 0, 'balance' => 0, 'overdue' => 0, 'term' => 0,
            'status' => 'pending', 'city' => 'İzmir', 'district' => 'Bornova',
            'email' => 'yeni@egeotomasyon.test', 'sales_rep' => null,
        ],
        'marmara' => [
            'code' => 'BY-2026-0003', 'title' => 'Marmara Yapı Teknolojileri A.Ş.',
            'brand' => 'Marmara Yapı', 'tax_office' => 'Gebze', 'tax_number' => '3456789012',
            'tier' => 'silver', 'price_list' => null, 'discount' => 6.00,
            'credit' => 120000, 'balance' => 30000, 'overdue' => 5000, 'term' => 30,
            'status' => 'active', 'city' => 'Kocaeli', 'district' => 'Gebze',
            'email' => 'sahibi@marmarayapi.test', 'sales_rep' => 'satis@b2b.test',
        ],
        'baskent' => [
            'code' => 'BY-2026-0004', 'title' => 'Başkent Endüstri Ürünleri Tic. A.Ş.',
            'brand' => 'Başkent Endüstri', 'tax_office' => 'Çankaya', 'tax_number' => '4567890123',
            'tier' => 'platinum', 'price_list' => 'PL-VIP', 'discount' => 15.00,
            'credit' => 500000, 'balance' => 120000, 'overdue' => 0, 'term' => 60,
            'status' => 'active', 'city' => 'Ankara', 'district' => 'Çankaya',
            'email' => 'sahibi@baskentendustri.test', 'sales_rep' => 'satis@b2b.test',
        ],
        'karadeniz' => [
            'code' => 'BY-2026-0005', 'title' => 'Karadeniz Hırdavat Pazarlama Ltd.',
            'brand' => 'Karadeniz Hırdavat', 'tax_office' => 'Ortahisar', 'tax_number' => '5678901234',
            'tier' => 'bronze', 'price_list' => null, 'discount' => 3.00,
            'credit' => 50000, 'balance' => 48000, 'overdue' => 12000, 'term' => 15,
            'status' => 'suspended', 'city' => 'Trabzon', 'district' => 'Ortahisar',
            'email' => 'sahibi@karadenizhirdavat.test', 'sales_rep' => 'satis@b2b.test',
        ],
    ],
    'users' => [
        ['company' => null, 'name' => 'Sistem Yöneticisi', 'email' => 'admin@b2b.test', 'password' => 'Admin123!', 'role' => 'super_admin', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => null, 'name' => 'Mehmet Satış', 'email' => 'satis@b2b.test', 'password' => 'Satis123!', 'role' => 'sales_manager', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => null, 'name' => 'Ayşe Finans', 'email' => 'finans@b2b.test', 'password' => 'Finans123!', 'role' => 'finance_admin', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => 'anadolu', 'name' => 'Hakan Yılmaz', 'email' => 'bayi@anadoluelektrik.test', 'password' => 'Bayi123!', 'role' => 'company_owner', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => 'anadolu', 'name' => 'Elif Kaya', 'email' => 'onay@anadoluelektrik.test', 'password' => 'Bayi123!', 'role' => 'purchasing_manager', 'limit' => 100000, 'vp' => 1, 'vf' => 1],
        ['company' => 'anadolu', 'name' => 'Burak Demir', 'email' => 'satinalma@anadoluelektrik.test', 'password' => 'Bayi123!', 'role' => 'buyer', 'limit' => 15000, 'vp' => 1, 'vf' => 0],
        ['company' => 'anadolu', 'name' => 'Selin Arslan', 'email' => 'muhasebe@anadoluelektrik.test', 'password' => 'Bayi123!', 'role' => 'accountant', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => 'ege', 'name' => 'Deniz Aydın', 'email' => 'yeni@egeotomasyon.test', 'password' => 'Bayi123!', 'role' => 'company_owner', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => 'marmara', 'name' => 'Cem Öztürk', 'email' => 'sahibi@marmarayapi.test', 'password' => 'Bayi123!', 'role' => 'company_owner', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => 'baskent', 'name' => 'Zeynep Şahin', 'email' => 'sahibi@baskentendustri.test', 'password' => 'Bayi123!', 'role' => 'company_owner', 'limit' => null, 'vp' => 1, 'vf' => 1],
        ['company' => 'karadeniz', 'name' => 'Ali Çelik', 'email' => 'sahibi@karadenizhirdavat.test', 'password' => 'Bayi123!', 'role' => 'company_owner', 'limit' => null, 'vp' => 1, 'vf' => 1],
    ],
];
