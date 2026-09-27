<?php

namespace RielPay\Laravel\Console;

use Illuminate\Console\Command;
use RielPay\Laravel\RielPayClient;

/** The RielPay logo for the terminal: "RIEL" in the default colour, "PAY" in KHQR red. */
class Banner
{
    protected const RED = '#E1232E';

    // ANSI Shadow letters, 6 rows each.
    protected const LETTERS = [
        'R' => ['██████╗ ', '██╔══██╗', '██████╔╝', '██╔══██╗', '██║  ██║', '╚═╝  ╚═╝'],
        'I' => ['██╗', '██║', '██║', '██║', '██║', '╚═╝'],
        'E' => ['███████╗', '██╔════╝', '█████╗  ', '██╔══╝  ', '███████╗', '╚══════╝'],
        'L' => ['██╗     ', '██║     ', '██║     ', '██║     ', '███████╗', '╚══════╝'],
        'P' => ['██████╗ ', '██╔══██╗', '██████╔╝', '██╔═══╝ ', '██║     ', '╚═╝     '],
        'A' => [' █████╗ ', '██╔══██╗', '███████║', '██╔══██║', '██║  ██║', '╚═╝  ╚═╝'],
        'Y' => ['██╗   ██╗', '╚██╗ ██╔╝', ' ╚████╔╝ ', '  ╚██╔╝  ', '   ██║   ', '   ╚═╝   '],
    ];

    public static function render(Command $command, string $subtitle = 'KHQR payments for Laravel'): void
    {
        // Write to the underlying Symfony output: Laravel 13's console style (laravel/pao, when an
        // AI agent runs the command) strips box-drawing characters and collapses spaces.
        $out = $command->getOutput()->getOutput();
        $footer = $subtitle.' · v'.RielPayClient::version().' · rielpays.com';

        // No colours (AI agent, CI, piped output): one plain line instead of the big logo.
        if (! $out->isDecorated()) {
            $out->writeln('RielPay · '.$footer);
            $out->writeln('');

            return;
        }

        $out->writeln('');
        for ($row = 0; $row < 6; $row++) {
            $riel = implode('', array_map(fn ($l) => self::LETTERS[$l][$row], str_split('RIEL')));
            $pay = implode('', array_map(fn ($l) => self::LETTERS[$l][$row], str_split('PAY')));
            $out->writeln('  <fg=white;options=bold>'.$riel.'</><fg='.self::RED.';options=bold>'.$pay.'</>');
        }
        $out->writeln('');
        $out->writeln('  <fg=gray>'.$footer.'</>');
        $out->writeln('');
    }

    /** The logo as plain text (no colours) — used by tests. */
    public static function plain(): string
    {
        $lines = [];
        for ($row = 0; $row < 6; $row++) {
            $lines[] = implode('', array_map(fn ($l) => self::LETTERS[$l][$row], str_split('RIELPAY')));
        }

        return implode("\n", $lines);
    }
}
