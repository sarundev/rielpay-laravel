<?php

namespace RielPay\Laravel;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/** One page of payments. Use $list->hasMore and the last payment's id to fetch the next page. */
class PaymentList implements Countable, IteratorAggregate
{
    /** @param Payment[] $data */
    public function __construct(public readonly array $data, public readonly bool $hasMore)
    {
    }

    public function last(): ?Payment
    {
        return $this->data[array_key_last($this->data)] ?? null;
    }

    public function count(): int
    {
        return count($this->data);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->data);
    }
}
