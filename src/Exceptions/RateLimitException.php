<?php

namespace RielPay\Laravel\Exceptions;

/** Too many requests (429). Slow down and retry. */
class RateLimitException extends RielPayException
{
}
