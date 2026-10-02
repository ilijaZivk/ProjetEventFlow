<?php

declare(strict_types=1);

final class BookingItem
{
    public function __construct(
        public readonly Ticket $ticket,
        public readonly int $quantity
    ) {
    }

    public function hasValidQuantity(): bool
    {
        return $this->quantity > 0;
    }

    public function lineTotal(): Money
    {
        return $this->ticket->price->multiply($this->quantity);
    }
}