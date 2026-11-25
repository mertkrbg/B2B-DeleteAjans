<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CartService;

/**
 * Class CartController
 *
 * B2B Sepet ve Sipariş Şablonları Kontrolcüsü.
 * Canlı sepet operasyonları (ekle, sil, miktar güncelle), kademeli iskonto hesaplama,
 * sepeti şablon olarak kaydetme ve şablondan sepete aktarma akışlarını yönetir.
 *
 * @package App\Controllers
 */
class CartController extends PortalController
{
    private function back(string $default = 'portal/katalog'): Response
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer !== '' && str_starts_with($referer, (string) config('app.url'))) {
            return Response::redirect($referer);
        }
        return Response::redirect(url($default));
    }

    public function index(Request $request): Response
    {
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        $items = CartService::items($cartId);
        return $this->renderPortal('cart', [
            'title'     => 'Sepetim',
            'items'     => $items,
            'totals'    => CartService::totals($items),
            'cartId'    => $cartId,
            'cart'      => \App\Core\Database::fetch('SELECT * FROM carts WHERE id = :id', [':id' => $cartId]) ?? [],
            'templates' => CartService::templates($this->companyId(), (int) Session::authId()),
            'warehouses'=> \App\Core\Database::fetchAll('SELECT id, name, city FROM warehouses WHERE is_active = 1 ORDER BY priority_order'),
        ]);
    }

    public function add(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $productId = $request->int('product_id');
        $quantity = $request->int('quantity');
        $warehouseId = $request->int('warehouse_id');
        if ($productId <= 0 || $quantity <= 0) {
            Session::flash('error', 'Geçersiz ürün veya adet bilgisi.');
            return $this->back();
        }
        $result = CartService::addItem(
            $this->companyId(),
            (int) Session::authId(),
            $productId,
            $quantity,
            $warehouseId > 0 ? $warehouseId : null
        );
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        return $result['ok'] ? $this->back() : $this->back();
    }

    public function update(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        $itemId = $request->int('item_id');
        $quantity = $request->int('quantity');
        $warehouseId = $request->int('warehouse_id');
        $result = CartService::updateQuantity($cartId, $itemId, $quantity, $this->companyId(), $warehouseId > 0 ? $warehouseId : null);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        return Response::redirect(url('portal/sepet'));
    }

    public function settings(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        CartService::saveSettings($cartId, $request->string('po_number'), $request->string('notes'));
        Session::flash('success', 'Sepet ayarları kaydedildi.');
        return Response::redirect(url('portal/sepet'));
    }

    public function saveTemplate(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $name = $request->string('template_name');
        $result = CartService::saveTemplate($this->companyId(), (int) Session::authId(), $name);
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        return Response::redirect(url('portal/sepet'));
    }

    public function loadTemplate(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $result = CartService::loadTemplate($request->int('template_id'), $this->companyId(), (int) Session::authId());
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);
        foreach ($result['errors'] ?? [] as $error) {
            Session::flash('error', $error);
        }
        return Response::redirect(url('portal/sepet'));
    }

    public function remove(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        $itemId = $request->int('item_id');
        if (CartService::removeItem($cartId, $itemId)) {
            Session::flash('success', 'Ürün sepetten çıkarıldı.');
        } else {
            Session::flash('error', 'Ürün bulunamadı.');
        }
        return Response::redirect(url('portal/sepet'));
    }
}
