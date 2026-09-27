<?php

namespace RielPay\Laravel\Tests;

use Illuminate\Support\Facades\Blade;
use RielPay\Laravel\Payment;

class KhqrCardTest extends TestCase
{
    public function test_live_payment_shows_the_qr_and_aba_button(): void
    {
        $html = Blade::render('<x-rielpay-khqr :payment="$p" merchant="My Shop" />', ['p' => new Payment($this->paymentJson())]);

        $this->assertStringContainsString('https://rielpays.com/api/render/khqr/'.rawurlencode($this->paymentJson()['qr_string']).'.svg', $html);
        $this->assertStringContainsString('Open in ABA Mobile', $html);
        $this->assertStringContainsString('My Shop', $html);
        $this->assertStringContainsString('$4.50', $html);
    }

    public function test_test_mode_payment_links_to_checkout(): void
    {
        $p = new Payment($this->paymentJson(['mode' => 'test', 'qr_string' => 'TEST-KHQR|x', 'deeplink' => null, 'currency' => 'KHR', 'amount' => '18000']));
        $html = Blade::render('<x-rielpay-khqr :payment="$p" />', ['p' => $p]);

        $this->assertStringNotContainsString('/api/render/khqr/', $html);
        $this->assertStringContainsString('https://rielpays.com/pay/pay_123', $html);
        $this->assertStringContainsString('18,000 ៛', $html);
        $this->assertStringNotContainsString('Open in ABA Mobile', $html);
    }
}
