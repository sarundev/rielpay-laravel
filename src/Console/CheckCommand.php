<?php

namespace RielPay\Laravel\Console;

use Illuminate\Console\Command;

class CheckCommand extends Command
{
    use ChecksSetup;

    protected $signature = 'rielpay:check';

    protected $description = 'Check your RielPay configuration and the connection to the API';

    public function handle(): int
    {
        Banner::render($this, 'Setup check');

        return $this->runChecks() ? self::SUCCESS : self::FAILURE;
    }
}
