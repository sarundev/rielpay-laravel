<?php

namespace RielPay\Laravel\Exceptions;

/** The request was rejected (400/409/422): check $details for the invalid fields. */
class InvalidRequestException extends RielPayException
{
}
