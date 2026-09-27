<?php

namespace RielPay\Laravel\Console;

use RielPay\Laravel\Exceptions\RielPayException;
use RielPay\Laravel\RielPayClient;

/** The configuration + connection checks shared by rielpay:check and rielpay:install. */
trait ChecksSetup
{
    /** Returns true when everything needed is in place. */
    public function runChecks(): bool
    {
        $ok = true;
        $key = config('rielpay.api_key');
        $secret = config('rielpay.webhook_secret');
        $webhookPath = config('rielpay.webhook_path');

        $this->components->twoColumnDetail('API endpoint', config('rielpay.base_url'));

        if (! $key) {
            $this->components->twoColumnDetail('API key', '<fg=red;options=bold>MISSING</> set RIELPAY_API_KEY');
            $ok = false;
        } elseif (! str_starts_with($key, 'sk_')) {
            $this->components->twoColumnDetail('API key', '<fg=yellow;options=bold>UNUSUAL</> keys start with sk_');
        } else {
            $this->components->twoColumnDetail('API key', '<fg=green;options=bold>SET</> '.substr($key, 0, 7).'…');
        }

        $this->components->twoColumnDetail('Webhook secret', $secret
            ? '<fg=green;options=bold>SET</>'
            : '<fg=yellow;options=bold>MISSING</> webhooks will be rejected');

        $this->components->twoColumnDetail('Webhook URL', $webhookPath
            ? rtrim(config('app.url'), '/').'/'.ltrim($webhookPath, '/')
            : '<fg=gray>disabled (your own route)</>');

        if ($key) {
            try {
                $list = $this->laravel->make(RielPayClient::class)->listPayments(['limit' => 1]);
                $this->components->twoColumnDetail('Connection', '<fg=green;options=bold>OK</> key accepted by RielPay'
                    .($list->count() ? '' : ' (no payments yet)'));
            } catch (RielPayException $e) {
                $this->components->twoColumnDetail('Connection', '<fg=red;options=bold>FAILED</> '.($e->errorCode ?? 'error'));
                $this->newLine();
                $this->components->error($e->getMessage());
                $ok = false;
            }
        }

        $this->newLine();

        return $ok;
    }
}
