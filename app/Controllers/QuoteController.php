<?php declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Services\CartService;
use App\Services\QuoteService;

/**
 * Class QuoteController
 *
 * B2B Fiyat Teklifi (RFQ) Talep ve Takip Kontrolcüsü.
 * Bayilerin proje veya toplu alım bazlı özel fiyat talebi oluşturmasını,
 * merkezden gelen karşı teklifleri incelemesini ve onaylayıp sepete aktarmasını yönetir.
 *
 * @package App\Controllers
 */
class QuoteController extends PortalController
{
    public function index(Request $request): Response
    {
        return $this->renderPortal('quotes', [
            'title'  => 'Tekliflerim (RFQ)',
            'quotes' => QuoteService::listForCompany($this->companyId()),
        ]);
    }

    public function create(Request $request): Response
    {
        if ($response = $this->requesterGuard()) {
            return $response;
        }
        $cartId = CartService::activeCart($this->companyId(), (int) Session::authId());
        $items = CartService::items($cartId);
        return $this->renderPortal('quote_create', [
            'title' => 'Teklif Talebi Oluştur',
            'items' => $items,
        ]);
    }

    public function store(Request $request): Response
    {
        if ($response = $this->requesterGuard()) {
            return $response;
        }
        $include = (array) $request->input('include', []);
        $quantities = (array) $request->input('quantity', []);
        $requested = (array) $request->input('requested_price', []);
        $lines = [];
        foreach ($include as $productId) {
            $pid = (int) $productId;
            $lines[] = [
                'product_id' => $pid,
                'quantity' => (int) ($quantities[$pid] ?? 0),
                'requested_price' => $requested[$pid] ?? null,
            ];
        }
        $result = QuoteService::create($this->companyId(), (int) Session::authId(), $lines, $request->string('customer_note'));
        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            return Response::redirect(url('portal/teklif-olustur'));
        }
        Session::flash('success', $result['message']);
        return Response::redirect(url('portal/teklif/' . $result['quote_number']));
    }

    public function show(Request $request, string $quoteNumber): Response
    {
        $quote = QuoteService::find($this->companyId(), $quoteNumber);
        if ($quote === null) {
            return Response::html(View::render('errors.404', ['title' => 'Teklif Bulunamadı'], 'auth'), 404);
        }
        return $this->renderPortal('quote_detail', [
            'title' => 'Teklif ' . $quote['quote_number'],
            'quote' => $quote,
            'items' => QuoteService::items((int) $quote['id']),
        ]);
    }

    public function accept(Request $request, string $quoteNumber): Response
    {
        if ($response = $this->requesterGuard()) {
            return $response;
        }
        $result = QuoteService::convertToOrder($this->companyId(), (int) Session::authId(), $quoteNumber);
        if (!$result['ok']) {
            Session::flash('error', $result['message']);
            return Response::redirect(url('portal/teklif/' . $quoteNumber));
        }
        Session::flash('success', $result['message']);
        return Response::redirect(url('portal/siparis/' . $result['order_number']));
    }

    private function requesterGuard(): ?Response
    {
        $role = (string) Session::get('role', '');
        if (!in_array($role, ['company_owner', 'purchasing_manager', 'buyer'], true)) {
            Session::flash('error', 'Teklif talep etme yetkiniz bulunmuyor.');
            return Response::redirect(url('portal/teklifler'));
        }
        return null;
    }
}
