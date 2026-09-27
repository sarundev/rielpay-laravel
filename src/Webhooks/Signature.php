<?php

namespace RielPay\Laravel\Webhooks;

use RielPay\Laravel\Exceptions\InvalidSignatureException;

/**
 * Verifies the X-Webhook-Signature header RielPay sends with every webhook:
 *   t=<unix time>,v1=<hex HMAC-SHA256(secret, "<t>.<raw body>")>
 */
class Signature
{
    public static function verify(string $payload, ?string $header, ?string $secret, int $toleranceSeconds = 300, ?int $now = null): void
    {
        if (! $secret) {
            throw new InvalidSignatureException('RielPay webhook secret is not configured (RIELPAY_WEBHOOK_SECRET).', 'missing_secret');
        }
        if (! $header) {
            throw new InvalidSignatureException('Missing X-Webhook-Signature header.', 'missing_signature');
        }

        $parts = [];
        foreach (explode(',', $header) as $pair) {
            [$key, $value] = array_pad(explode('=', trim($pair), 2), 2, null);
            if ($key !== null && $value !== null) {
                $parts[$key] = $value;
            }
        }
        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;
        if (! $timestamp || ! ctype_digit($timestamp) || ! $signature) {
            throw new InvalidSignatureException('Malformed X-Webhook-Signature header.', 'malformed_signature');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        if (! hash_equals($expected, $signature)) {
            throw new InvalidSignatureException('Webhook signature does not match. Check RIELPAY_WEBHOOK_SECRET.', 'invalid_signature');
        }

        if ($toleranceSeconds > 0 && abs(($now ?? time()) - (int) $timestamp) > $toleranceSeconds) {
            throw new InvalidSignatureException('Webhook signature is too old (possible replay).', 'expired_signature');
        }
    }

    /** Build a valid header — handy for tests of your own webhook handling. */
    public static function sign(string $payload, string $secret, ?int $timestamp = null): string
    {
        $timestamp ??= time();

        return 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }
}
