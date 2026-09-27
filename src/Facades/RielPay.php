<?php

namespace RielPay\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use RielPay\Laravel\RielPayClient;

/**
 * @method static \RielPay\Laravel\Payment createPayment(array $params, ?string $idempotencyKey = null)
 * @method static \RielPay\Laravel\Payment getPayment(string $id)
 * @method static \RielPay\Laravel\PaymentList listPayments(array $params = [])
 * @method static \Generator allPayments(array $params = [])
 *
 * @see RielPayClient
 */
class RielPay extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return RielPayClient::class;
    }
}
