<?php

namespace RielPay\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallCommand extends Command
{
    use ChecksSetup;

    protected $signature = 'rielpay:install
        {--key= : Your secret API key (sk_...)}
        {--webhook-secret= : Your webhook signing secret (whsec_...)}';

    protected $description = 'Set up RielPay: publish the config, save your keys to .env and test the connection';

    public function handle(): int
    {
        Banner::render($this);

        // 1. Config file
        if (File::exists(config_path('rielpay.php'))) {
            $this->components->twoColumnDetail('Config', '<fg=gray>config/rielpay.php already exists</>');
        } else {
            $this->callSilently('vendor:publish', ['--tag' => 'rielpay-config']);
            $this->components->twoColumnDetail('Config', '<fg=green;options=bold>PUBLISHED</> config/rielpay.php');
        }

        // 2. Keys → .env (asked for interactively; hidden while typing)
        $this->line('  <fg=gray>Find these in the RielPay dashboard → Stores → your store.</>');
        $key = $this->option('key') ?? $this->askSecretFor('rielpay.api_key', 'Secret API key (sk_...)');
        $secret = $this->option('webhook-secret') ?? $this->askSecretFor('rielpay.webhook_secret', 'Webhook signing secret (whsec_...)');

        $this->writeEnv([
            'RIELPAY_API_KEY' => $key,
            'RIELPAY_WEBHOOK_SECRET' => $secret,
        ]);

        // 3. Check everything, including a real call to the API
        $this->newLine();
        $ok = $this->runChecks();

        // 4. Next steps
        $webhook = rtrim(config('app.url'), '/').'/'.ltrim((string) config('rielpay.webhook_path'), '/');
        $this->components->info($ok ? 'RielPay is ready.' : 'Almost there — fix the items above, then run php artisan rielpay:check.');
        $this->line('  <options=bold>Next steps</>');
        $this->line('  1. Set your webhook URL in the dashboard: <fg=cyan>'.$webhook.'</>');
        $this->line("  2. Create a payment:  <fg=cyan>RielPay::createPayment(['amount' => 4.50, 'currency' => 'USD'])</>");
        $this->line('  3. Listen for <fg=cyan>RielPay\Laravel\Events\PaymentSucceeded</> to fulfil orders.');
        $this->line('  Docs: <fg=cyan>https://rielpays.com/docs#sdk-laravel</>');
        $this->newLine();

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** Ask for a value unless it's already set; empty answers keep the current value. */
    protected function askSecretFor(string $configKey, string $question): ?string
    {
        if (! $this->input->isInteractive()) {
            return null;
        }
        $current = config($configKey);
        $answer = $this->secret($current ? "{$question} — leave empty to keep the current one" : $question);

        return $answer !== null && trim($answer) !== '' ? trim($answer) : null;
    }

    /**
     * Set values in .env: replace an existing line (so there are never duplicates), otherwise
     * append it. Null values are left alone, but a missing key gets an empty placeholder.
     */
    protected function writeEnv(array $values): void
    {
        $path = $this->laravel->environmentFilePath();
        $contents = File::exists($path) ? File::get($path) : '';
        $changed = [];

        foreach ($values as $name => $value) {
            $pattern = '/^'.preg_quote($name, '/').'=.*$/m';
            $exists = preg_match($pattern, $contents) === 1;
            if ($value === null) {
                if (! $exists) {
                    $contents = rtrim($contents)."\n{$name}=\n";
                }
                continue;
            }
            $line = $name.'='.$this->quote($value);
            $contents = $exists ? preg_replace($pattern, $line, $contents, 1) : rtrim($contents)."\n{$line}\n";
            $changed[] = $name;
            putenv("{$name}={$value}");
            $_ENV[$name] = $_SERVER[$name] = $value;
        }

        File::put($path, $contents);

        // Make the new values visible to the rest of this command.
        config([
            'rielpay.api_key' => env('RIELPAY_API_KEY', config('rielpay.api_key')),
            'rielpay.webhook_secret' => env('RIELPAY_WEBHOOK_SECRET', config('rielpay.webhook_secret')),
        ]);
        $this->laravel->forgetInstance(\RielPay\Laravel\RielPayClient::class);

        $this->components->twoColumnDetail('.env', $changed
            ? '<fg=green;options=bold>UPDATED</> '.implode(', ', $changed)
            : '<fg=gray>unchanged</>');
    }

    protected function quote(string $value): string
    {
        return preg_match('/\s|#|"/', $value) ? '"'.addcslashes($value, '"').'"' : $value;
    }
}
