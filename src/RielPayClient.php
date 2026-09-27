<?php

namespace RielPay\Laravel;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RielPay\Laravel\Exceptions\ApiConnectionException;
use RielPay\Laravel\Exceptions\AuthenticationException;
use RielPay\Laravel\Exceptions\RielPayException;

/** Thin, typed client for the RielPay REST API (https://rielpays.com/docs). */
class RielPayClient
{
    /** Fallback when Composer can't tell us the installed version. */
    public const VERSION = '1.2.5';

    /** The installed package version, e.g. "1.2.5" (read from Composer, so it always matches the tag). */
    public static function version(): string
    {
        try {
            $v = \Composer\InstalledVersions::getPrettyVersion('rielpays/laravel');
        } catch (\Throwable) {
            $v = null;
        }

        return $v && preg_match('/^v?\d+\.\d+\.\d+$/', $v) ? ltrim($v, 'v') : self::VERSION;
    }

    public function __construct(
        protected HttpFactory $http,
        protected ?string $apiKey,
        protected string $baseUrl = 'https://rielpays.com',
        protected int $timeout = 30,
        protected int $retries = 2,
    ) {
    }

    /**
     * Create a payment. Returns the KHQR (`qr_string`), `deeplink` and hosted `checkout_url`.
     *
     * @param  array{amount: float|int|string, currency?: 'USD'|'KHR', description?: string,
     *               metadata?: array<string, string|int|float|bool>, redirect_url?: string,
     *               expires_in_minutes?: int}  $params
     * @param  string|null  $idempotencyKey  Reuse the same key (e.g. "order-1024") to make retries safe.
     *                                       One is generated if omitted, so automatic retries never double-charge.
     */
    public function createPayment(array $params, ?string $idempotencyKey = null): Payment
    {
        if (! isset($params['amount'])) {
            throw new InvalidArgumentException('RielPay: "amount" is required.');
        }
        $idempotencyKey ??= (string) Str::uuid();

        $body = $this->send('post', '/v1/payments', $params, ['Idempotency-Key' => $idempotencyKey]);

        return new Payment($body);
    }

    /** Fetch the latest state of a payment (live payments are re-checked with ABA). */
    public function getPayment(string $id): Payment
    {
        return new Payment($this->send('get', '/v1/payments/'.rawurlencode($id)));
    }

    /**
     * List payments, newest first.
     *
     * @param  array{limit?: int, status?: string, starting_after?: string}  $params
     */
    public function listPayments(array $params = []): PaymentList
    {
        $body = $this->send('get', '/v1/payments', $params);

        return new PaymentList(
            array_map(fn (array $p) => new Payment($p), $body['data'] ?? []),
            (bool) ($body['has_more'] ?? false),
        );
    }

    /** Iterate every payment across pages (lazy). */
    public function allPayments(array $params = []): \Generator
    {
        do {
            $page = $this->listPayments($params);
            foreach ($page as $payment) {
                yield $payment;
            }
            $params['starting_after'] = $page->last()?->id;
        } while ($page->hasMore && $params['starting_after']);
    }

    protected function send(string $method, string $path, array $data = [], array $headers = []): array
    {
        if (! $this->apiKey) {
            throw new AuthenticationException('RielPay API key is missing. Set RIELPAY_API_KEY in your .env file.', 'missing_api_key');
        }

        $attempt = 0;
        while (true) {
            $attempt++;
            try {
                $response = $this->request($headers)->{$method}($path, $data);
            } catch (ConnectionException $e) {
                if ($attempt <= $this->retries) {
                    $this->backoff($attempt);
                    continue;
                }
                throw new ApiConnectionException('Could not reach RielPay: '.$e->getMessage(), 'connection_error', null, null, $e);
            }

            // Temporary failures (e.g. a server restart or ABA hiccup): retry. Safe for
            // create because the same Idempotency-Key returns the original payment.
            if (in_array($response->status(), [502, 503, 504], true) && $attempt <= $this->retries) {
                $this->backoff($attempt);
                continue;
            }

            return $this->decode($response);
        }
    }

    protected function request(array $headers): PendingRequest
    {
        return $this->http
            ->baseUrl(rtrim($this->baseUrl, '/'))
            ->withToken($this->apiKey)
            ->withHeaders($headers + ['User-Agent' => 'rielpay-laravel/'.self::version()])
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout);
    }

    protected function decode(Response $response): array
    {
        $body = $response->json() ?? [];
        if ($response->successful()) {
            return is_array($body) ? $body : [];
        }

        throw RielPayException::fromResponse($response->status(), is_array($body) ? $body : []);
    }

    protected function backoff(int $attempt): void
    {
        usleep((int) (500_000 * 2 ** ($attempt - 1))); // 0.5s, 1s, 2s…
    }
}
