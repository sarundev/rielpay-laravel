<?php

namespace RielPay\Laravel\Exceptions;

/** RielPay or ABA is temporarily unavailable (5xx). Safe to retry with the same idempotency key. */
class ServiceUnavailableException extends RielPayException
{
}
