<?php declare(strict_types=1);

/**
 * B2B Enterprise Portal - Uygulama Rota Tanımları (Routing)
 *
 * Tüm public, auth, bayi portalı ve ERP/admin rotalarını,
 * ilgili HTTP fiilleri ve middleware yetkilendirme zincirleri ile kaydeder.
 *
 * @param \App\Core\Router $router
 */

use App\Controllers\AuthController;
use App\Controllers\CartController;
use App\Controllers\CatalogController;
use App\Controllers\CheckoutController;
use App\Controllers\CompanyTeamController;
use App\Controllers\DashboardController;
use App\Controllers\FinanceController;
use App\Controllers\InstallController;
use App\Controllers\OrderController;
use App\Controllers\QuickOrderController;
use App\Controllers\QuoteController;
use App\Controllers\Admin\AdminCompanyController;
use App\Controllers\Admin\AdminDashboardController;
use App\Controllers\Admin\AdminFinanceController;
use App\Controllers\Admin\AdminOrderController;
use App\Controllers\Admin\AdminPriceListController;
use App\Controllers\Admin\AdminProductController;
use App\Controllers\Admin\AdminQuoteController;
use App\Controllers\Admin\AdminSettingsController;
use App\Middleware\AdminMiddleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Router;
use App\Core\Session;
use App\Middleware\AuthMiddleware;
use App\Middleware\B2BApprovedMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\PermissionMiddleware;

return static function (Router $router): void {
    $router->get('/kurulum', [InstallController::class, 'index']);
    $router->post('/kurulum', [InstallController::class, 'install'], [CsrfMiddleware::class]);

    $router->get('/', static function (Request $request): Response {
        if (Session::isAuthenticated()) {
            $role = (string) Session::get('role', '');
            $isAdmin = in_array($role, ['super_admin', 'sales_manager', 'finance_admin'], true);
            return Response::redirect(url($isAdmin ? 'admin' : 'portal'));
        }
        return Response::redirect(url('login'));
    });

    $router->get('/login', [AuthController::class, 'showLogin']);
    $router->post('/login', [AuthController::class, 'login'], [CsrfMiddleware::class]);
    $router->get('/bayi-basvuru', [AuthController::class, 'showRegister']);
    $router->post('/bayi-basvuru', [AuthController::class, 'register'], [CsrfMiddleware::class]);
    $router->get('/logout', [AuthController::class, 'logout']);

    // ---- Bayi Portalı ----
    $router->get('/portal', [DashboardController::class, 'index'], [
        AuthMiddleware::class,
        B2BApprovedMiddleware::class,
        [PermissionMiddleware::class, ['portal.catalog.view']],
    ]);

    $portalGuard = [
        AuthMiddleware::class,
        B2BApprovedMiddleware::class,
        [PermissionMiddleware::class, ['portal.catalog.view']],
    ];

    $router->get('/portal/katalog', [CatalogController::class, 'index'], $portalGuard);
    $router->get('/portal/urun/{slug}', [CatalogController::class, 'show'], $portalGuard);

    $router->get('/portal/hizli-siparis', [QuickOrderController::class, 'index'], $portalGuard);
    $router->get('/portal/hizli-siparis/ara', [QuickOrderController::class, 'search'], $portalGuard);
    $router->get('/portal/hizli-siparis/fiyat', [QuickOrderController::class, 'price'], $portalGuard);
    $router->get('/portal/hizli-siparis/sablon', [QuickOrderController::class, 'template'], $portalGuard);
    $router->post('/portal/hizli-siparis/aktar', [QuickOrderController::class, 'transfer'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/hizli-siparis/toplu', [QuickOrderController::class, 'bulk'], array_merge($portalGuard, [CsrfMiddleware::class]));

    $router->get('/portal/sepet', [CartController::class, 'index'], $portalGuard);
    $router->post('/portal/sepet/ekle', [CartController::class, 'add'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/sepet/guncelle', [CartController::class, 'update'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/sepet/sil', [CartController::class, 'remove'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/sepet/ayarlar', [CartController::class, 'settings'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/sepet/sablon-kaydet', [CartController::class, 'saveTemplate'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/sepet/sablon-yukle', [CartController::class, 'loadTemplate'], array_merge($portalGuard, [CsrfMiddleware::class]));

    $router->get('/portal/siparis-tamamla', [CheckoutController::class, 'show'], $portalGuard);
    $router->post('/portal/siparis-tamamla', [CheckoutController::class, 'place'], array_merge($portalGuard, [CsrfMiddleware::class]));

    $router->get('/portal/siparisler', [OrderController::class, 'index'], $portalGuard);
    $router->get('/portal/siparis/{no}', [OrderController::class, 'show'], $portalGuard);
    $router->get('/portal/siparis/{no}/yazdir', [OrderController::class, 'printOrder'], $portalGuard);
    $router->post('/portal/siparis/{no}/tekrar', [OrderController::class, 'reorder'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/siparis/{no}/onayla', [OrderController::class, 'approve'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/siparis/{no}/reddet', [OrderController::class, 'reject'], array_merge($portalGuard, [CsrfMiddleware::class]));

    $router->get('/portal/teklifler', [QuoteController::class, 'index'], $portalGuard);
    $router->get('/portal/teklif-olustur', [QuoteController::class, 'create'], $portalGuard);
    $router->post('/portal/teklif-olustur', [QuoteController::class, 'store'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->get('/portal/teklif/{no}', [QuoteController::class, 'show'], $portalGuard);
    $router->post('/portal/teklif/{no}/kabul', [QuoteController::class, 'accept'], array_merge($portalGuard, [CsrfMiddleware::class]));

    $router->get('/portal/cari-hesap', [FinanceController::class, 'index'], $portalGuard);
    $router->get('/portal/cari-hesap/csv', [FinanceController::class, 'exportCsv'], $portalGuard);
    $router->get('/portal/cari-hesap/yazdir', [FinanceController::class, 'printStatement'], $portalGuard);
    $router->post('/portal/cari-hesap/odeme', [FinanceController::class, 'notifyPayment'], array_merge($portalGuard, [CsrfMiddleware::class]));

    $router->get('/portal/ekip', [CompanyTeamController::class, 'index'], $portalGuard);
    $router->post('/portal/ekip', [CompanyTeamController::class, 'store'], array_merge($portalGuard, [CsrfMiddleware::class]));
    $router->post('/portal/ekip/guncelle', [CompanyTeamController::class, 'update'], array_merge($portalGuard, [CsrfMiddleware::class]));

    // ---- Admin ERP ----
    $adminGuard = [AuthMiddleware::class, AdminMiddleware::class];

    $router->get('/admin', [AdminDashboardController::class, 'index'], $adminGuard);

    $router->get('/admin/bayiler', [AdminCompanyController::class, 'index'], $adminGuard);
    $router->get('/admin/bayiler/{id}', [AdminCompanyController::class, 'show'], $adminGuard);
    $router->post('/admin/bayiler/{id}', [AdminCompanyController::class, 'update'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->post('/admin/bayiler/{id}/onayla', [AdminCompanyController::class, 'approve'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->post('/admin/bayiler/{id}/reddet', [AdminCompanyController::class, 'reject'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->get('/admin/bayiler/{id}/vergi-levhasi', [AdminCompanyController::class, 'taxPlate'], $adminGuard);

    $router->get('/admin/fiyat-listeleri', [AdminPriceListController::class, 'index'], $adminGuard);
    $router->post('/admin/fiyat-listeleri', [AdminPriceListController::class, 'store'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->get('/admin/fiyat-listeleri/{id}', [AdminPriceListController::class, 'show'], $adminGuard);
    $router->post('/admin/fiyat-listeleri/{id}', [AdminPriceListController::class, 'updateItems'], array_merge($adminGuard, [CsrfMiddleware::class]));

    $router->get('/admin/urunler', [AdminProductController::class, 'index'], $adminGuard);
    $router->get('/admin/urunler/yeni', [AdminProductController::class, 'create'], $adminGuard);
    $router->post('/admin/urunler', [AdminProductController::class, 'store'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->get('/admin/urunler/{id}', [AdminProductController::class, 'edit'], $adminGuard);
    $router->post('/admin/urunler/{id}', [AdminProductController::class, 'update'], array_merge($adminGuard, [CsrfMiddleware::class]));

    $router->get('/admin/siparisler', [AdminOrderController::class, 'index'], $adminGuard);
    $router->get('/admin/siparisler/{no}', [AdminOrderController::class, 'show'], $adminGuard);
    $router->post('/admin/siparisler/{no}/onayla', [AdminOrderController::class, 'approve'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->post('/admin/siparisler/{no}/sevk', [AdminOrderController::class, 'ship'], array_merge($adminGuard, [CsrfMiddleware::class]));
    $router->post('/admin/siparisler/{no}/fatura', [AdminOrderController::class, 'invoice'], array_merge($adminGuard, [CsrfMiddleware::class]));

    $router->get('/admin/teklifler', [AdminQuoteController::class, 'index'], $adminGuard);
    $router->get('/admin/teklifler/{no}', [AdminQuoteController::class, 'show'], $adminGuard);
    $router->post('/admin/teklifler/{no}', [AdminQuoteController::class, 'offer'], array_merge($adminGuard, [CsrfMiddleware::class]));

    $financeGuard = array_merge($adminGuard, [[PermissionMiddleware::class, ['admin.finance.manage']]]);
    $router->get('/admin/finans', [AdminFinanceController::class, 'index'], $financeGuard);
    $router->post('/admin/finans/tahsilat', [AdminFinanceController::class, 'manual'], array_merge($financeGuard, [CsrfMiddleware::class]));
    $router->post('/admin/finans/{id}/onayla', [AdminFinanceController::class, 'confirm'], array_merge($financeGuard, [CsrfMiddleware::class]));
    $router->post('/admin/finans/{id}/reddet', [AdminFinanceController::class, 'reject'], array_merge($financeGuard, [CsrfMiddleware::class]));
    $router->get('/admin/finans/{id}/dekont', [AdminFinanceController::class, 'receipt'], $financeGuard);

    $settingsGuard = array_merge($adminGuard, [[PermissionMiddleware::class, ['admin.settings.manage']]]);
    $router->get('/admin/ayarlar', [AdminSettingsController::class, 'index'], $settingsGuard);
    $router->post('/admin/ayarlar', [AdminSettingsController::class, 'updateSettings'], array_merge($settingsGuard, [CsrfMiddleware::class]));
    $router->post('/admin/ayarlar/kur', [AdminSettingsController::class, 'updateRates'], array_merge($settingsGuard, [CsrfMiddleware::class]));
};
