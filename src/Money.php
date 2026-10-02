<?php

declare(strict_types=1);

final class Money
{
    private function __construct(private readonly int $cents)
    {
        if ($cents < 0) {
            throw new InvalidArgumentException('Money cannot be negative');
        }
    }

    public static function fromCents(int $cents): self
    {
        return new self($cents);
    }

    public static function fromEuros(float $euros): self
    {
        return new self((int) round($euros * 100));
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public function cents(): int
    {
        return $this->cents;
    }

    public function toFloat(): float
    {
        return $this->cents / 100;
    }

    public function add(self $other): self
    {
        return new self($this->cents + $other->cents);
    }

    public function multiply(int $factor): self
    {
        return new self($this->cents * $factor);
    }

    public function applyDiscountPercent(int $percent): self
    {
        return new self((int) round($this->cents * (100 - $percent) / 100));
    }

    public function subtractOrZero(self $other): self
    {
        return new self(max(0, $this->cents - $other->cents));
    }

    public function isZero(): bool
    {
        return $this->cents === 0;
    }

    public function format(): string
    {
        return number_format($this->toFloat(), 2, '.', '');
    }
}