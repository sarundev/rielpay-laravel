<?php

namespace RielPay\Laravel\Tests;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RielPay\Laravel\RielPayClient;

class CommandsTest extends TestCase
{
    private string $envDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envDir = sys_get_temp_dir().'/rielpay-env-'.uniqid();
        File::ensureDirectoryExists($this->envDir);
        $this->app->useEnvironmentPath($this->envDir);
        File::delete(config_path('rielpay.php'));
    }

    protected function tearDown(): void
    {
        foreach (['RIELPAY_API_KEY', 'RIELPAY_WEBHOOK_SECRET'] as $name) {
            putenv($name);
            unset($_ENV[$name], $_SERVER[$name]);
        }
        File::deleteDirectory($this->envDir);
        File::delete(config_path('rielpay.php'));
        parent::tearDown();
    }

    private function env(): string
    {
        return File::get($this->envDir.'/.env');
    }

    public function test_check_shows_the_banner_and_passes_with_a_working_key(): void
    {
        Http::fake(['*' => Http::response(['object' => 'list', 'has_more' => false, 'data' => []])]);

        // Test output isn't decorated, so the compact header is used instead of the big logo.
        $this->artisan('rielpay:check')
            ->expectsOutputToContain('RielPay · Setup check')
            ->expectsOutputToContain('rielpays.com')
            ->assertExitCode(0);
    }

    public function test_colour_terminals_get_the_full_logo(): void
    {
        $output = new \Symfony\Component\Console\Output\BufferedOutput(decorated: true);
        $command = new \RielPay\Laravel\Console\CheckCommand;
        $command->setLaravel($this->app);
        $command->setOutput(new \Illuminate\Console\OutputStyle(new \Symfony\Component\Console\Input\ArrayInput([]), $output));

        \RielPay\Laravel\Console\Banner::render($command, 'Setup check');

        $text = preg_replace('/\e\[[0-9;]*m/', '', $output->fetch());
        foreach (explode("\n", \RielPay\Laravel\Console\Banner::plain()) as $row) {
            $this->assertStringContainsString($row, $text); // every logo row intact, incl. ╗ ║ ╝
        }
    }

    public function test_check_fails_without_a_key(): void
    {
        config(['rielpay.api_key' => null]);
        $this->artisan('rielpay:check')->assertExitCode(1);
    }

    public function test_check_reports_a_rejected_key(): void
    {
        Http::fake(['*' => Http::response(['error' => ['code' => 'invalid_api_key', 'message' => 'Invalid or revoked API key']], 401)]);
        $this->artisan('rielpay:check')->expectsOutputToContain('Invalid or revoked API key')->assertExitCode(1);
    }

    public function test_install_writes_keys_without_duplicating_existing_lines(): void
    {
        File::put($this->envDir.'/.env', "APP_NAME=Shop\nRIELPAY_API_KEY=\nRIELPAY_WEBHOOK_SECRET=\n");
        Http::fake(['*' => Http::response(['object' => 'list', 'has_more' => false, 'data' => []])]);

        $this->artisan('rielpay:install', ['--key' => 'sk_live_new', '--webhook-secret' => 'whsec_new', '--no-interaction' => true])
            ->expectsOutputToContain('RielPay · KHQR payments for Laravel')
            ->expectsOutputToContain('RielPay is ready.')
            ->assertExitCode(0);

        $env = $this->env();
        $this->assertSame(1, substr_count($env, 'RIELPAY_API_KEY='));
        $this->assertSame(1, substr_count($env, 'RIELPAY_WEBHOOK_SECRET='));
        $this->assertStringContainsString("RIELPAY_API_KEY=sk_live_new\n", $env);
        $this->assertStringContainsString("RIELPAY_WEBHOOK_SECRET=whsec_new\n", $env);
        $this->assertStringContainsString('APP_NAME=Shop', $env);
        $this->assertFileExists(config_path('rielpay.php'));
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer sk_live_new'));
    }

    public function test_install_without_keys_adds_placeholders_once(): void
    {
        File::put($this->envDir.'/.env', "APP_NAME=Shop\n");
        config(['rielpay.api_key' => null]);
        $this->app->forgetInstance(RielPayClient::class);

        $this->artisan('rielpay:install', ['--no-interaction' => true])->assertExitCode(1);
        $this->artisan('rielpay:install', ['--no-interaction' => true])->assertExitCode(1);

        $this->assertSame(1, substr_count($this->env(), 'RIELPAY_API_KEY='));
        $this->assertSame(1, substr_count($this->env(), 'RIELPAY_WEBHOOK_SECRET='));
    }

    public function test_install_asks_for_keys_interactively(): void
    {
        File::put($this->envDir.'/.env', "APP_NAME=Shop\n");
        config(['rielpay.api_key' => null, 'rielpay.webhook_secret' => null]);
        $this->app->forgetInstance(RielPayClient::class);
        Http::fake(['*' => Http::response(['object' => 'list', 'has_more' => false, 'data' => []])]);

        $this->artisan('rielpay:install')
            ->expectsQuestion('Secret API key (sk_...)', 'sk_typed')
            ->expectsQuestion('Webhook signing secret (whsec_...)', 'whsec_typed')
            ->assertExitCode(0);

        $this->assertStringContainsString('RIELPAY_API_KEY=sk_typed', $this->env());
    }
}
