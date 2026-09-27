<?php

namespace RielPay\Laravel\Tests;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RielPay\Laravel\Exceptions\ApiConnectionException;
use RielPay\Laravel\Exceptions\AuthenticationException;
use RielPay\Laravel\Exceptions\CurrencyMismatchException;
use RielPay\Laravel\Exceptions\NotFoundException;
use RielPay\Laravel\Exceptions\PlanException;
use RielPay\Laravel\Exceptions\ServiceUnavailableException;
use RielPay\Laravel\Facades\RielPay;
use RielPay\Laravel\RielPayClient;

class ClientTest extends TestCase
{
    public function test_create_payment_sends_key_and_idempotency_key(): void
    {
        Http::fake(['rielpays.com/v1/payments' => Http::response($this->paymentJson(), 201)]);

        $payment = RielPay::createPayment(['amount' => 4.5, 'currency' => 'USD', 'metadata' => ['order_id' => '1024']], 'order-1024');

        $this->assertSame('pay_123', $payment->id);
        $this->assertTrue($payment->isPending());
        $this->assertSame('1024', $payment->metadata('order_id'));
        $this->assertSame(4.5, $payment->amountValue());
        Http::assertSent(fn (Request $r) => $r->method() === 'POST'
            && $r->url() === 'https://rielpays.com/v1/payments'
            && $r->hasHeader('Authorization', 'Bearer sk_test_123')
            && $r->hasHeader('Idempotency-Key', 'order-1024')
            && $r['amount'] === 4.5);
    }

    public function test_generates_an_idempotency_key_when_none_given(): void
    {
        Http::fake(['*' => Http::response($this->paymentJson(), 201)]);
        RielPay::createPayment(['amount' => 1]);
        Http::assertSent(fn (Request $r) => strlen($r->header('Idempotency-Key')[0] ?? '') === 36);
    }

    public function test_retries_temporary_failures_with_the_same_idempotency_key(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['error' => ['code' => 'provider_error', 'message' => 'Bad gateway']], 502)
            ->push($this->paymentJson(), 201)]);

        $payment = RielPay::createPayment(['amount' => 1], 'order-9');

        $this->assertSame('pay_123', $payment->id);
        $keys = [];
        Http::assertSent(function (Request $r) use (&$keys) { $keys[] = $r->header('Idempotency-Key')[0]; return true; });
        $this->assertSame(['order-9', 'order-9'], $keys);
    }

    public function test_gives_up_after_the_configured_retries(): void
    {
        config(['rielpay.retries' => 1]);
        $this->app->forgetInstance(RielPayClient::class);
        RielPay::clearResolvedInstances();
        Http::fake(['*' => Http::response(['error' => ['code' => 'busy', 'message' => 'Too many live payments']], 503)]);

        $this->expectException(ServiceUnavailableException::class);
        try {
            RielPay::createPayment(['amount' => 1]);
        } finally {
            Http::assertSentCount(2);
        }
    }

    public function test_maps_errors_to_specific_exceptions(): void
    {
        $cases = [
            [401, 'invalid_api_key', AuthenticationException::class],
            [402, 'trial_ended', PlanException::class],
            [402, 'plan_expired', PlanException::class],
            [400, 'currency_mismatch', CurrencyMismatchException::class],
            [404, 'not_found', NotFoundException::class],
        ];
        foreach ($cases as [$status, $code, $class]) {
            $http = new \Illuminate\Http\Client\Factory;
            $http->fake(['*' => $http->response(['error' => ['code' => $code, 'message' => "msg {$code}"]], $status)]);
            try {
                (new RielPayClient($http, 'sk_test_123'))->getPayment('pay_x');
                $this->fail("Expected {$class}");
            } catch (\Throwable $e) {
                $this->assertInstanceOf($class, $e, $code);
                $this->assertSame($code, $e->errorCode);
                $this->assertSame($status, $e->httpStatus);
                $this->assertSame("msg {$code}", $e->getMessage());
            }
        }
    }

    public function test_missing_api_key_fails_clearly(): void
    {
        config(['rielpay.api_key' => null]);
        $this->app->forgetInstance(RielPayClient::class);
        RielPay::clearResolvedInstances();
        Http::fake();

        $this->expectException(AuthenticationException::class);
        $this->expectExceptionMessage('RIELPAY_API_KEY');
        RielPay::getPayment('pay_1');
    }

    public function test_connection_errors_become_api_connection_exception(): void
    {
        config(['rielpay.retries' => 0]);
        $this->app->forgetInstance(RielPayClient::class);
        RielPay::clearResolvedInstances();
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out'));

        $this->expectException(ApiConnectionException::class);
        RielPay::getPayment('pay_1');
    }

    public function test_lists_and_iterates_all_pages(): void
    {
        Http::fake([
            'rielpays.com/v1/payments?limit=2' => Http::response(['object' => 'list', 'has_more' => true, 'data' => [
                $this->paymentJson(['id' => 'pay_3']), $this->paymentJson(['id' => 'pay_2']),
            ]]),
            'rielpays.com/v1/payments?limit=2&starting_after=pay_2' => Http::response(['object' => 'list', 'has_more' => false, 'data' => [
                $this->paymentJson(['id' => 'pay_1', 'status' => 'paid']),
            ]]),
        ]);

        $page = RielPay::listPayments(['limit' => 2]);
        $this->assertCount(2, $page);
        $this->assertTrue($page->hasMore);

        $ids = array_map(fn ($p) => $p->id, iterator_to_array(RielPay::allPayments(['limit' => 2]), false));
        $this->assertSame(['pay_3', 'pay_2', 'pay_1'], $ids);
    }
}
