<?php

namespace RielPay\Laravel\View;

use Illuminate\View\Component;
use RielPay\Laravel\Payment;

/**
 * <x-rielpay-khqr :payment="$payment" />
 * Shows a payment as a KHQR card on your own page: the QR image (rendered by RielPay),
 * the amount, an "Open in ABA Mobile" button on phones, and a link to the hosted checkout.
 */
class KhqrCard extends Component
{
    public ?string $qrImageUrl;

    public function __construct(public Payment $payment, public ?string $merchant = null)
    {
        $qr = $payment->qr_string;
        // RielPay renders only real KHQR strings; test-mode payments link to checkout instead.
        $this->qrImageUrl = $qr && $payment->isLive()
            ? rtrim(config('rielpay.base_url'), '/').'/api/render/khqr/'.rawurlencode($qr).'.svg'
            : null;
    }

    public function amountText(): string
    {
        $value = $this->payment->amountValue();

        return $this->payment->currency === 'KHR'
            ? number_format($value, 0).' ៛'
            : '$'.number_format($value, 2);
    }

    public function render()
    {
        return view('rielpay::khqr-card');
    }
}
