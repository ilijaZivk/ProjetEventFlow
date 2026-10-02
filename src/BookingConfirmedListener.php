<?php

declare(strict_types=1);

interface BookingConfirmedListener
{
    public function onBookingConfirmed(Booking $booking): void;
}
