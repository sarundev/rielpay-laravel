<?php

namespace RielPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;
use RielPay\Laravel\Payment;

/** Base for payment webhooks: $payment is the payment as it was when the event happened. */
abstract class PaymentEvent
{
    use Dispatchable;

    public function __construct(public readonly Payment $payment, public readonly array $event)
    {
    }
}
