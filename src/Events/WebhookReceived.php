<?php

namespace RielPay\Laravel\Events;

use Illuminate\Foundation\Events\Dispatchable;

/** Fired for every verified webhook (including webhook.test), before the specific event. */
class WebhookReceived
{
    use Dispatchable;

    /** @param array{id: string, type: string, created: string, data: array|null} $event */
    public function __construct(public readonly array $event)
    {
    }
}
