<?php

declare(strict_types=1);

final class TestRunner
{
    private int $passed = 0;
    private int $failed = 0;

    public function same(mixed $expected, mixed $actual, string $label): void
    {
        if ($expected === $actual) {
            $this->passed++;
            echo "OK   {$label}" . PHP_EOL;
            return;
        }

        $this->failed++;
        echo "FAIL {$label}" . PHP_EOL;
        echo '     expected: ' . var_export($expected, true) . PHP_EOL;
        echo '     actual:   ' . var_export($actual, true) . PHP_EOL;
    }

    public function near(float $expected, float $actual, string $label, float $delta = 0.001): void
    {
        if (abs($expected - $actual) <= $delta) {
            $this->passed++;
            echo "OK   {$label}" . PHP_EOL;
            return;
        }

        $this->failed++;
        echo "FAIL {$label}" . PHP_EOL;
        echo "     expected: {$expected}" . PHP_EOL;
        echo "     actual:   {$actual}" . PHP_EOL;
    }

    public function throws(callable $call, string $expectedMessage, string $label): void
    {
        try {
            $call();
        } catch (Throwable $e) {
            if ($e->getMessage() === $expectedMessage) {
                $this->passed++;
                echo "OK   {$label}" . PHP_EOL;
                return;
            }

            $this->failed++;
            echo "FAIL {$label}" . PHP_EOL;
            echo "     expected exception: {$expectedMessage}" . PHP_EOL;
            echo "     actual exception:   {$e->getMessage()}" . PHP_EOL;
            return;
        }

        $this->failed++;
        echo "FAIL {$label}" . PHP_EOL;
        echo "     expected exception: {$expectedMessage}" . PHP_EOL;
        echo '     actual:             no exception' . PHP_EOL;
    }

    public function summary(): void
    {
        echo PHP_EOL . "Passed: {$this->passed}, Failed: {$this->failed}" . PHP_EOL;
        if ($this->failed > 0) {
            exit(1);
        }
    }
}