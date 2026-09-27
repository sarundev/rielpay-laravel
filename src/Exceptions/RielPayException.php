<?php

namespace RielPay\Laravel\Exceptions;

use RuntimeException;

/** Base class for every error returned by the RielPay API. */
class RielPayException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        public readonly ?int $httpStatus = null,
        public readonly mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus ?? 0, $previous);
    }

    /** Build the most specific exception for an API error response. */
    public static function fromResponse(int $status, array $body): self
    {
        $error = $body['error'] ?? [];
        $code = $error['code'] ?? null;
        $message = $error['message'] ?? "RielPay API error (HTTP {$status})";
        $details = $error['details'] ?? null;

        $class = match (true) {
            $status === 401 => AuthenticationException::class,
            $code === 'currency_mismatch' => CurrencyMismatchException::class,
            $status === 402 => PlanException::class,
            $status === 403 => AuthenticationException::class,
            $status === 404 => NotFoundException::class,
            $status === 400, $status === 409, $status === 422 => InvalidRequestException::class,
            $status === 429 => RateLimitException::class,
            $status >= 500 => ServiceUnavailableException::class,
            default => self::class,
        };

        return new $class($message, $code, $status, $details);
    }
}
