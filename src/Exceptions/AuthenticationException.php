<?php

namespace RielPay\Laravel\Exceptions;

/** Invalid, revoked or missing API key (401), or the account is suspended (403). */
class AuthenticationException extends RielPayException
{
}
