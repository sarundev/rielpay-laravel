<?php

namespace RielPay\Laravel;

use ArrayAccess;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use JsonSerializable;
use LogicException;

/**
 * A RielPay payment, as returned by the API.
 *
 * @property-read string $id
 * @property-read string $mode            "test" | "live"
 * @property-read string $status          "pending" | "paid" | "expired" | "failed"
 * @property-read string $amount          Decimal string, e.g. "4.50"
 * @property-read string $currency        "USD" | "KHR"
 * @property-read string|null $description
 * @property-read array $metadata
 * @property-read string|null $qr_string  The KHQR to show (live mode)
 * @property-read string|null $deeplink   Opens ABA Mobile on phones (live mode)
 * @property-read string $checkout_url    Hosted checkout page
 * @property-read string|null $redirect_url
 * @property-read string|null $provider_reference
 */
class Payment implements ArrayAccess, Arrayable, Jsonable, JsonSerializable
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const EXPIRED = 'expired';
    public const FAILED = 'failed';

    public function __construct(protected array $attributes)
    {
    }

    public function isPending(): bool
    {
        return $this->status === self::PENDING;
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function isExpired(): bool
    {
        return $this->status === self::EXPIRED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }

    public function isLive(): bool
    {
        return $this->mode === 'live';
    }

    /** One metadata value you attached when creating the payment, e.g. metadata('order_id'). */
    public function metadata(string $key, mixed $default = null): mixed
    {
        return $this->attributes['metadata'][$key] ?? $default;
    }

    /** Amount as a number (USD with cents, KHR whole riel). */
    public function amountValue(): float
    {
        return (float) $this->attributes['amount'];
    }

    public function expiresAt(): ?CarbonImmutable
    {
        return $this->date('expires_at');
    }

    public function paidAt(): ?CarbonImmutable
    {
        return $this->date('paid_at');
    }

    public function createdAt(): ?CarbonImmutable
    {
        return $this->date('created_at');
    }

    protected function date(string $key): ?CarbonImmutable
    {
        return empty($this->attributes[$key]) ? null : CarbonImmutable::parse($this->attributes[$key]);
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new LogicException('RielPay payments are read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new LogicException('RielPay payments are read-only.');
    }

    public function toArray(): array
    {
        return $this->attributes;
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->attributes, $options | JSON_THROW_ON_ERROR);
    }

    public function jsonSerialize(): array
    {
        return $this->attributes;
    }
}
