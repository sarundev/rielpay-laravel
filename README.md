# RielPay for Laravel

Official Laravel SDK for **[RielPay](https://rielpays.com)** — accept **KHQR** payments from ABA, ACLEDA,
Wing and every Bakong bank in Cambodia. Money settles straight to your own ABA account.

- One call creates a payment with a KHQR, an ABA Mobile deeplink and a hosted checkout page
- Webhooks verified for you and turned into Laravel events
- Safe automatic retries (idempotency keys), clear exceptions, a ready-made KHQR Blade component

Requires PHP 8.1+ and Laravel 10, 11, 12 or 13.

## Installation

```bash
composer require sarundev/rielpay-laravel
```

Add your keys to `.env` (Dashboard → Stores → your store):

```dotenv
RIELPAY_API_KEY=sk_...           # API keys tab
RIELPAY_WEBHOOK_SECRET=whsec_... # Webhooks tab
```

Optionally publish the config: `php artisan vendor:publish --tag=rielpay-config`.

## Create a payment

```php
use RielPay\Laravel\Facades\RielPay;

public function checkout(Order $order)
{
    $payment = RielPay::createPayment([
        'amount'   => $order->total,          // e.g. 4.50 (USD) or 18000 (KHR)
        'currency' => 'USD',
        'description' => "Order #{$order->id}",
        'metadata' => ['order_id' => (string) $order->id],
        'redirect_url' => route('orders.show', $order), // optional
    ], idempotencyKey: "order-{$order->id}");        // same key = same payment, never charged twice

    $order->update(['rielpay_payment_id' => $payment->id]);

    return redirect($payment->checkout_url);          // RielPay's hosted KHQR page
}
```

Or show the KHQR on your own page:

```blade
<x-rielpay-khqr :payment="$payment" merchant="My Shop" />
```

It renders the red KHQR card with the QR, the amount and — on phones — an **Open in ABA Mobile** button.

`$payment` exposes every field of the API (`id`, `status`, `amount`, `currency`, `qr_string`, `deeplink`,
`checkout_url`, `metadata`, …) plus helpers: `isPaid()`, `isPending()`, `isExpired()`, `metadata('order_id')`,
`amountValue()`, `paidAt()`, `expiresAt()`.

## Confirm payment with webhooks

The package registers `POST /rielpay/webhook`. In the dashboard set your store's webhook URL to
`https://your-shop.com/rielpay/webhook`. Signatures are verified (with replay protection) and duplicate
deliveries are ignored. Listen for the events:

```php
use RielPay\Laravel\Events\PaymentSucceeded;

// app/Providers/AppServiceProvider.php → boot()
Event::listen(PaymentSucceeded::class, function (PaymentSucceeded $event) {
    $order = Order::find($event->payment->metadata('order_id'));
    $order?->markAsPaid($event->payment->id);
});
```

| Event | When |
|---|---|
| `PaymentSucceeded` | The customer paid — fulfil the order here |
| `PaymentExpired` | The QR expired unpaid |
| `PaymentFailed` | The payment failed |
| `PaymentCreated` | A payment was created |
| `WebhookReceived` | Every verified webhook, including the dashboard's "Send test" |

Always fulfil orders from the webhook, not from the redirect — customers can close the browser before
being redirected. If a listener throws, the endpoint returns 500 and RielPay retries later.

Want your own route? Set `RIELPAY_WEBHOOK_PATH=null` and verify manually:

```php
use RielPay\Laravel\Webhooks\Signature;

Signature::verify($request->getContent(), $request->header('X-Webhook-Signature'), config('rielpay.webhook_secret'));
```

## Other calls

```php
$payment = RielPay::getPayment('pay_...');          // latest status
$page    = RielPay::listPayments(['limit' => 20, 'status' => 'paid']);
foreach (RielPay::allPayments(['status' => 'paid']) as $payment) { /* every page, lazily */ }
```

## Errors

Every API error throws a subclass of `RielPay\Laravel\Exceptions\RielPayException` with `->errorCode`,
`->httpStatus` and `->details`:

| Exception | Meaning |
|---|---|
| `AuthenticationException` | Wrong/revoked key (401) or suspended account (403) |
| `PlanException` | `trial_ended`, `plan_expired` or `plan_limit` (402) — renew in the dashboard |
| `CurrencyMismatchException` | The store's ABA link charges in the other currency |
| `InvalidRequestException` | Invalid parameters (400/409/422) |
| `NotFoundException` | Unknown payment (404) |
| `ServiceUnavailableException` / `ApiConnectionException` | Temporary problem — retried automatically (2×) |

## Test mode

Stores start in **test mode**: payments get a demo QR and the hosted checkout shows a *Simulate payment*
button, which fires the real `payment.succeeded` webhook. Switch the store to **Live** when you're ready.

---

## ភាសាខ្មែរ — ការណែនាំខ្លី

1. ដំឡើង៖ `composer require sarundev/rielpay-laravel`
2. ដាក់ `RIELPAY_API_KEY` និង `RIELPAY_WEBHOOK_SECRET` ក្នុង `.env` (យកពី Dashboard → Stores)
3. បង្កើត Payment៖ `RielPay::createPayment([...])` ហើយ Redirect ទៅ `$payment->checkout_url`
   ឬបង្ហាញ QR ក្នុងទំព័ររបស់អ្នក៖ `<x-rielpay-khqr :payment="$payment" />`
4. ដាក់ Webhook URL `https://your-shop.com/rielpay/webhook` ក្នុង Dashboard ហើយស្ដាប់ Event `PaymentSucceeded`
   ដើម្បីបញ្ជាក់ការបញ្ជាទិញ (Signature ត្រូវបាន Verify ដោយស្វ័យប្រវត្តិ)
5. ប្រើ `idempotencyKey` (ឧ. `order-1024`) ដើម្បីកុំឲ្យបង់ប្រាក់ពីរដង

ឯកសារ API ពេញ៖ https://rielpays.com/docs · Telegram៖ @RielPay2bot

## License

MIT
