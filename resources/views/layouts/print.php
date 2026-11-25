<?php
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(($title ?? 'Belge') . ' · B2B Enterprise') ?></title>
    <link rel="icon" type="image/svg+xml" href="<?= asset('images/logo.svg') ?>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --ink:#0F172A; --line:#E2E8F0; }
        body { font-family: Inter, system-ui, sans-serif; color:#0F172A; }
        .mono { font-family:'JetBrains Mono', ui-monospace, monospace; font-variant-numeric: tabular-nums; }
        @media print {
            .no-print { display:none !important; }
            body { background:#fff !important; }
            @page { size: A4; margin: 14mm; }
            .sheet { box-shadow:none !important; border:none !important; margin:0 !important; max-width:none !important; }
        }
        @media screen and (max-width: 640px) {
            .sheet { max-width: 100% !important; padding: 0.75rem !important; margin-left: 0 !important; margin-right: 0 !important; }
        }
    </style>
</head>
<body class="bg-slate-100">
    <div class="no-print sticky top-0 z-10 flex items-center justify-between border-b border-slate-200 bg-white px-4 py-2.5">
        <a href="<?= url('portal/siparisler') ?>" class="text-sm font-semibold text-slate-600 hover:text-action">← Siparişlere Dön</a>
        <button onclick="window.print()" class="rounded-lg bg-action px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">Yazdır / PDF Kaydet</button>
    </div>
    <div class="mx-auto my-6 max-w-[210mm] bg-white p-6 shadow-lg sm:p-10 sheet">
        <?= $content ?>
    </div>
</body>
</html>
