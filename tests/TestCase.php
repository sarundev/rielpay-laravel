<?php

namespace RielPay\Laravel\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use RielPay\Laravel\RielPayServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [RielPayServiceProvider::class];
    }

    protected function getPackageAliases($app): array
    {
        return ['RielPay' => \RielPay\Laravel\Facades\RielPay::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('rielpay.api_key', 'sk_test_123');
        $app['config']->set('rielpay.webhook_secret', 'whsec_test');
        $app['config']->set('rielpay.base_url', 'https://rielpays.com');
        $app['config']->set('rielpay.retries', 2);
        $app['config']->set('cache.default', 'array');
    }

    protected function paymentJson(array $overrides = []): array
    {
        return array_merge([
            'id' => 'pay_123', 'object' => 'payment', 'mode' => 'live', 'status' => 'pending',
            'amount' => '4.50', 'currency' => 'USD', 'description' => 'Order #1024',
            'metadata' => ['order_id' => '1024'],
            'qr_string' => '00020101021230510016abaakhppxxx@abaa6304ABCD',
            'deeplink' => 'abamobilebank://ababank.com?type=payway&qrcode=000201',
            'checkout_url' => 'https://rielpays.com/pay/pay_123', 'redirect_url' => null,
            'provider_reference' => null, 'expires_at' => '2026-09-27T10:03:00.000Z',
            'paid_at' => null, 'created_at' => '2026-09-27T10:00:00.000Z',
        ], $overrides);
    }
}
