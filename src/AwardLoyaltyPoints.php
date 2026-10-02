<?php

declare(strict_types=1);

final class AwardLoyaltyPoints implements BookingConfirmedListener
{
    private const CENTS_PER_POINT = 100;

    public function __construct(private readonly LoyaltyService $loyaltyService)
    {
    }

    public function onBookingConfirmed(Booking $booking): void
    {
        $points = intdiv($booking->total()?->cents() ?? 0, self::CENTS_PER_POINT);

        if ($points > 0) {
            $this->loyaltyService->addPoints($booking->customer->id, $points);
        }
    }
}
