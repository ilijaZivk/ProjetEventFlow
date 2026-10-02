<?php

declare(strict_types=1);

interface BookingRepository
{
    public function save(Booking $booking): void;
}
