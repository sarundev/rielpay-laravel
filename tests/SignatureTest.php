<?php

namespace RielPay\Laravel\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase as BaseTestCase;
use RielPay\Laravel\Exceptions\InvalidSignatureException;
use RielPay\Laravel\Webhooks\Signature;

class SignatureTest extends BaseTestCase
{
    private string $body = '{"id":"evt_1","type":"payment.succeeded"}';

    public function test_accepts_a_valid_signature(): void
    {
        Signature::verify($this->body, Signature::sign($this->body, 'whsec_x'), 'whsec_x');
        $this->addToAssertionCount(1);
    }

    public function test_matches_the_server_format(): void
    {
        // Same computation as RielPay's server: HMAC-SHA256(secret, "<t>.<body>") as hex.
        $header = 't=1700000000,v1='.hash_hmac('sha256', '1700000000.'.$this->body, 'whsec_x');
        Signature::verify($this->body, $header, 'whsec_x', 300, 1700000100);
        $this->addToAssertionCount(1);
    }

    #[DataProvider('badCases')]
    public function test_rejects_bad_signatures(string $body, ?string $header, ?string $secret, string $code): void
    {
        try {
            Signature::verify($body, $header, $secret, 300, 1700000000);
            $this->fail('Expected InvalidSignatureException');
        } catch (InvalidSignatureException $e) {
            $this->assertSame($code, $e->errorCode);
        }
    }

    public static function badCases(): array
    {
        $body = '{"id":"evt_1","type":"payment.succeeded"}';
        $good = Signature::sign($body, 'whsec_x', 1700000000);

        return [
            'tampered body' => ['{"id":"evt_1","type":"payment.succeeded","x":1}', $good, 'whsec_x', 'invalid_signature'],
            'wrong secret' => [$body, $good, 'whsec_other', 'invalid_signature'],
            'too old' => [$body, Signature::sign($body, 'whsec_x', 1699990000), 'whsec_x', 'expired_signature'],
            'missing header' => [$body, null, 'whsec_x', 'missing_signature'],
            'malformed header' => [$body, 'nonsense', 'whsec_x', 'malformed_signature'],
            'no secret configured' => [$body, $good, null, 'missing_secret'],
        ];
    }
}
