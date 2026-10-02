<?php

declare(strict_types=1);

final class Ticket
{
    public function __construct(
        public readonly string $code,
        public readonly string $label,
        public readonly Money $price
    ) {
    }
}