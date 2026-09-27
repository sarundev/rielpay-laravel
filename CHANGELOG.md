# Changelog

## 1.2.2 — 2026-09-27

- `rielpay:install` repairs a key pasted twice into the hidden prompt, and re-asks when a key doesn't look like `sk_…`/`whsec_…` + 32 characters

## 1.2.1 — 2026-09-27

- Banner stays intact on Laravel 13 (written to the raw console output); without colours (AI agents, CI, pipes) a one-line header is shown instead of the big logo

## 1.2.0 — 2026-09-27

- `php artisan rielpay:install`: RielPay banner, publishes the config, asks for your keys, writes them to `.env` without duplicate lines and tests the connection
- `php artisan rielpay:check`: shows your configuration and verifies the API key against RielPay

## 1.1.0 — 2026-09-27

- Package renamed to `rielpays/laravel` (was `sarundev/rielpay-laravel`). Install with `composer require rielpays/laravel`; the PHP namespace `RielPay\Laravel` is unchanged, so no code changes are needed.

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
