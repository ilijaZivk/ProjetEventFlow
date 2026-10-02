<?php

declare(strict_types=1);

final class ConsoleBookingRepository implements BookingRepository
{
    public function save(Booking $booking): void
    {
        $total = $booking->total()?->format() ?? '0.00';

        echo "SQL INSERT booking={$booking->id} total={$total} status={$booking->status()->value}" . PHP_EOL;
    }
}
