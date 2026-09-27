<?php

namespace RielPay\Laravel\Tests;

use Illuminate\Support\Facades\Event;
use RielPay\Laravel\Events\PaymentExpired;
use RielPay\Laravel\Events\PaymentSucceeded;
use RielPay\Laravel\Events\WebhookReceived;
use RielPay\Laravel\Webhooks\Signature;

class WebhookTest extends TestCase
{
    private function send(array $event, ?string $secret = 'whsec_test')
    {
        $body = json_encode($event);
        $headers = ['CONTENT_TYPE' => 'application/json'];
        if ($secret) {
            $headers['HTTP_X_WEBHOOK_SIGNATURE'] = Signature::sign($body, $secret);
        }

        return $this->call('POST', '/rielpay/webhook', [], [], [], $headers, $body);
    }

    private function succeeded(string $id = 'evt_1'): array
    {
        return ['id' => $id, 'type' => 'payment.succeeded', 'created' => '2026-09-27T10:01:00Z',
            'data' => $this->paymentJson(['status' => 'paid', 'paid_at' => '2026-09-27T10:01:00Z'])];
    }

    public function test_dispatches_payment_succeeded(): void
    {
        Event::fake();

        $this->send($this->succeeded())->assertOk()->assertJson(['received' => true]);

        Event::assertDispatched(WebhookReceived::class, fn ($e) => $e->event['id'] === 'evt_1');
        Event::assertDispatched(PaymentSucceeded::class, fn ($e) => $e->payment->isPaid()
            && $e->payment->metadata('order_id') === '1024'
            && $e->payment->paidAt()?->toIso8601String() === '2026-09-27T10:01:00+00:00');
    }

    public function test_ignores_a_duplicate_delivery(): void
    {
        Event::fake();
        $this->send($this->succeeded('evt_dup'))->assertOk();
        $this->send($this->succeeded('evt_dup'))->assertOk()->assertJson(['duplicate' => true]);
        Event::assertDispatchedTimes(PaymentSucceeded::class, 1);
    }

    public function test_rejects_invalid_signatures(): void
    {
        Event::fake([WebhookReceived::class, PaymentSucceeded::class]);
        $this->send($this->succeeded(), 'whsec_wrong')->assertStatus(400);
        $this->send($this->succeeded(), null)->assertStatus(400);
        Event::assertNotDispatched(WebhookReceived::class);
        Event::assertNotDispatched(PaymentSucceeded::class);
    }

    public function test_test_events_only_fire_webhook_received(): void
    {
        Event::fake();
        $this->send(['id' => 'evt_t', 'type' => 'webhook.test', 'created' => 'x', 'data' => ['message' => 'hi']])->assertOk();
        Event::assertDispatched(WebhookReceived::class);
        Event::assertNotDispatched(PaymentSucceeded::class);
        Event::assertNotDispatched(PaymentExpired::class);
    }

    public function test_a_failing_listener_lets_rielpay_retry(): void
    {
        Event::listen(PaymentSucceeded::class, fn () => throw new \RuntimeException('db down'));
        $this->withoutExceptionHandling();
        try {
            $this->send($this->succeeded('evt_retry'));
            $this->fail('listener exception should bubble up (=> 500, so RielPay retries)');
        } catch (\RuntimeException) {
        }
        $this->assertFalse(cache()->has('rielpay:webhook:evt_retry'), 'event must not be marked as handled');
    }
}
