<?php

namespace RielPay\Laravel\Http;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RielPay\Laravel\Events\PaymentCreated;
use RielPay\Laravel\Events\PaymentExpired;
use RielPay\Laravel\Events\PaymentFailed;
use RielPay\Laravel\Events\PaymentSucceeded;
use RielPay\Laravel\Events\WebhookReceived;
use RielPay\Laravel\Exceptions\InvalidSignatureException;
use RielPay\Laravel\Payment;
use RielPay\Laravel\Webhooks\Signature;

/**
 * Receives RielPay webhooks: verifies the signature on the raw body, ignores duplicates
 * (RielPay retries until it gets a 2xx), and turns each event into a Laravel event.
 */
class WebhookController
{
    protected const EVENTS = [
        'payment.created' => PaymentCreated::class,
        'payment.succeeded' => PaymentSucceeded::class,
        'payment.expired' => PaymentExpired::class,
        'payment.failed' => PaymentFailed::class,
    ];

    public function __invoke(Request $request, Dispatcher $events, Cache $cache): JsonResponse
    {
        $payload = $request->getContent();
        try {
            Signature::verify(
                $payload,
                $request->header('X-Webhook-Signature'),
                config('rielpay.webhook_secret'),
                (int) config('rielpay.webhook_tolerance', 300),
            );
        } catch (InvalidSignatureException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }

        $event = json_decode($payload, true);
        if (! is_array($event) || empty($event['id']) || empty($event['type'])) {
            return new JsonResponse(['error' => 'Invalid webhook payload'], 400);
        }

        // Only skip an event after it was handled successfully, so a failed attempt is retried.
        $seenKey = 'rielpay:webhook:'.$event['id'];
        if ($cache->has($seenKey)) {
            return new JsonResponse(['received' => true, 'duplicate' => true]);
        }

        $events->dispatch(new WebhookReceived($event));
        if (isset(self::EVENTS[$event['type']]) && is_array($event['data'] ?? null)) {
            $class = self::EVENTS[$event['type']];
            $events->dispatch(new $class(new Payment($event['data']), $event));
        }

        $cache->put($seenKey, true, now()->addDays(2));

        return new JsonResponse(['received' => true]);
    }
}
