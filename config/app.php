<?php declare(strict_types=1);

return [
    'name'        => 'B2B Enterprise Portal',
    'short_name'  => 'B2B Enterprise',
    'url'         => 'http://b2b.test',
    'timezone'    => 'Europe/Istanbul',
    'locale'      => 'tr_TR',
    'currency'    => 'TRY',
    'currency_symbol' => '₺',
    'env'         => 'local',
    'debug'       => false,
    'vat_rates'   => [0.0, 1.0, 10.0, 20.0],
    'upload_max'  => 5 * 1024 * 1024,
    'allowed_upload_mimes' => ['application/pdf', 'image/jpeg', 'image/png'],
    'allowed_upload_ext'   => ['pdf', 'jpg', 'jpeg', 'png'],
    'session_name' => 'B2B_PORTAL_SESSID',
];
