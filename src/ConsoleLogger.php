<?php

declare(strict_types=1);

final class ConsoleLogger implements Logger
{
    public function info(string $message, array $context = []): void
    {
        $this->write('INFO', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    /** @param array<string, mixed> $context */
    private function write(string $level, string $message, array $context): void
    {
        echo "[{$level}] {$message} " . json_encode($context) . PHP_EOL;
    }
}
