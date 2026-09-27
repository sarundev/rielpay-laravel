<?php

namespace RielPay\Laravel\Exceptions;

/** A webhook's X-Webhook-Signature is missing, invalid or too old. */
class InvalidSignatureException extends RielPayException
{
}
