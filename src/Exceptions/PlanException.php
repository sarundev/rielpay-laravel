<?php

namespace RielPay\Laravel\Exceptions;

/** The store's plan blocks the request (402): trial_ended, plan_expired or plan_limit. Renew in the dashboard. */
class PlanException extends RielPayException
{
}
