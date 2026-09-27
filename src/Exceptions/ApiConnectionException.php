<?php

namespace RielPay\Laravel\Exceptions;

/** RielPay could not be reached (network error or timeout). Safe to retry with the same idempotency key. */
class ApiConnectionException extends RielPayException
{
}
