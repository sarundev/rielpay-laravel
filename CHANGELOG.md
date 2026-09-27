# Changelog

## 1.0.2 — 2026-09-27

- Don't pin Guzzle: use whatever Laravel's HTTP client needs (Laravel 13 uses Guzzle 8)

## 1.0.1 — 2026-09-27

- Support Laravel 13

## 1.0.0 — 2026-09-27

- `RielPay::createPayment()`, `getPayment()`, `listPayments()`, `allPayments()`
- Automatic idempotency keys and retries for temporary failures (network, 502/503/504)
- Typed exceptions for auth, plan (trial ended / expired), currency mismatch, validation, not found
- Webhook route with signature verification, replay protection, duplicate suppression and Laravel events
- `<x-rielpay-khqr>` Blade component
