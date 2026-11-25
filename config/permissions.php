<?php declare(strict_types=1);

return [
    'super_admin' => [
        'admin.panel', 'admin.companies.approve', 'admin.finance.manage',
        'admin.products.manage', 'admin.orders.manage', 'admin.quotes.manage',
        'admin.settings.manage', 'admin.reports.view',
    ],
    'sales_manager' => [
        'admin.panel', 'admin.companies.approve', 'admin.products.manage',
        'admin.orders.manage', 'admin.quotes.manage', 'admin.reports.view',
    ],
    'finance_admin' => [
        'admin.panel', 'admin.companies.approve', 'admin.finance.manage',
        'admin.orders.manage', 'admin.reports.view',
    ],
    'company_owner' => [
        'portal.catalog.view', 'portal.order.create', 'portal.order.approve',
        'portal.quote.request', 'portal.finance.view', 'portal.team.manage',
        'portal.order.view',
    ],
    'purchasing_manager' => [
        'portal.catalog.view', 'portal.order.create', 'portal.order.approve',
        'portal.quote.request', 'portal.finance.view.limited', 'portal.order.view',
    ],
    'buyer' => [
        'portal.catalog.view', 'portal.order.create.limited', 'portal.quote.request',
        'portal.order.view',
    ],
    'accountant' => [
        'portal.catalog.view', 'portal.finance.view', 'portal.order.view',
    ],
];
