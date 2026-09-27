<?php

namespace RielPay\Laravel\Exceptions;

/** The store's ABA payment link charges in a different currency than the payment (400). */
class CurrencyMismatchException extends InvalidRequestException
{
}
