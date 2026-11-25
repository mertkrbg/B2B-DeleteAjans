<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CreditRiskService;

/**
 * Class PortalController
 *
 * B2B Bayi Portalı Temel Soyut Kontrolcüsü (Base Controller).
 * Aktif bayi oturum bilgileri, firma cari özeti, sepet sayaçları
 * ve portal genel layout verilerini hazırlar.
 *
 * @package App\Controllers
 */
abstract class PortalController
{
    protected ?array $companyCache = null;

    protected function companyId(): int
    {
        return (int) Session::get('company_id', 0);
    }

    protected function company(): array
    {
        if ($this->companyCache === null) {
            $this->companyCache = Database::fetch(
                'SELECT * FROM companies WHERE id = :id LIMIT 1',
                [':id' => $this->companyId()]
            ) ?? [];
        }
        return $this->companyCache;
    }

    protected function credit(): array
    {
        return CreditRiskService::snapshot($this->companyId());
    }

    protected function cartSummary(): array
    {
        $cart = Database::fetch(
            'SELECT id FROM carts WHERE user_id = :uid AND is_template = 0 ORDER BY id DESC LIMIT 1',
            [':uid' => Session::authId()]
        );
        if ($cart === null) {
            return ['count' => 0, 'net' => 0.0, 'gross' => 0.0, 'cart_id' => null];
        }
        $items = \App\Services\CartService::items((int) $cart['id']);
        $totals = \App\Services\CartService::totals($items);
        return [
            'count' => $totals['count'],
            'net'   => $totals['net'],
            'gross' => $totals['gross'],
            'cart_id' => (int) $cart['id'],
        ];
    }

    protected function unreadNotifications(): int
    {
        return (int) Database::fetchColumn(
            'SELECT COUNT(*) FROM notifications WHERE user_id = :uid AND is_read = 0',
            [':uid' => Session::authId()]
        );
    }

    protected function canCreateOrders(): bool
    {
        return in_array((string) Session::get('role', ''), ['company_owner', 'purchasing_manager', 'buyer'], true);
    }

    protected function orderCreatorGuard(): ?Response
    {
        if (!$this->canCreateOrders()) {
            Session::flash('error', 'Sipariş oluşturma yetkiniz bulunmuyor. Lütfen firma yöneticiniz ile iletişime geçin.');
            return Response::redirect(url('portal'));
        }
        return null;
    }

    protected function renderPortal(string $template, array $data): Response
    {
        $company = $this->company();
        $credit = $this->credit();
        $cart = $this->cartSummary();
        $common = [
            'company'        => $company,
            'credit'         => $credit,
            'cartSummary'    => $cart,
            'unreadCount'    => $this->unreadNotifications(),
            'authUser'       => Session::auth(),
            'currentPath'    => parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/',
            'pageTitle'      => $data['title'] ?? 'Kokpit',
        ];
        $html = View::render('portal.' . $template, array_merge($common, $data), 'portal');
        return Response::html($html);
    }
}
