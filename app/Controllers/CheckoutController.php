<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\CartService;
use App\Services\OrderService;

/**
 * Class CheckoutController
 *
 * B2B Sipariş Tamamlama (Checkout) Kontrolcüsü.
 * Sevkiyat/fatura adresi seçimi, ödeme yöntemi tercihi (açık hesap, havale, kredi kartı),
 * sipariş özeti ve sipariş oluşturma onay akışını yönetir.
 *
 * @package App\Controllers
 */
class CheckoutController extends PortalController
{
    public function show(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        $items = CartService::items($cartId);
        if (empty($items)) {
            Session::flash('warning', 'Sipariş tamamlamak için sepetinizde ürün olmalıdır.');
            return Response::redirect(url('portal/sepet'));
        }
        $addresses = Database::fetchAll(
            'SELECT * FROM company_addresses WHERE company_id = :co ORDER BY is_default DESC, id ASC',
            [':co' => $this->companyId()]
        );
        $settings = [];
        foreach (Database::fetchAll("SELECT setting_key, setting_value FROM system_settings WHERE group_name IN ('payment','general')") as $s) {
            $settings[$s['setting_key']] = $s['setting_value'];
        }
        $cart = Database::fetch('SELECT * FROM carts WHERE id = :id', [':id' => $cartId]) ?? [];
        $user = Database::fetch('SELECT order_approval_limit, role FROM users WHERE id = :id', [':id' => Session::authId()]);

        return $this->renderPortal('checkout', [
            'title'     => 'Sipariş Tamamla',
            'items'     => $items,
            'totals'    => CartService::totals($items),
            'addresses' => $addresses,
            'credit'    => $this->credit(),
            'cart'      => $cart,
            'settings'  => $settings,
            'user'      => $user,
        ]);
    }

    public function place(Request $request): Response
    {
        if ($response = $this->orderCreatorGuard()) {
            return $response;
        }
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        $items = CartService::items($cartId);
        if (empty($items)) {
            Session::flash('warning', 'Sepetiniz boş. Lütfen ürün ekleyin.');
            return Response::redirect(url('portal/sepet'));
        }

        $result = OrderService::createFromCart($this->companyId(), (int) Session::authId(), [
            'items'                => $items,
            'shipping_address_id'  => $request->int('shipping_address_id'),
            'billing_address_id'   => $request->int('billing_address_id'),
            'payment_method'       => $request->string('payment_method'),
            'customer_po_number'   => $request->string('customer_po_number'),
            'customer_note'        => $request->string('customer_note'),
        ]);

        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            return Response::redirect(url('portal/siparis-tamamla'));
        }

        Session::flash('success', $result['message'] . ' Sipariş No: ' . $result['order_number']);
        return Response::redirect(url('portal/siparis/' . $result['order_number']));
    }
}
