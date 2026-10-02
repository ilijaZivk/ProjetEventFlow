<?php

declare(strict_types=1);

final class TrackBookingConfirmed implements BookingConfirmedListener
{
    public function __construct(private readonly AnalyticsClient $analytics)
    {
    }

    public function onBookingConfirmed(Booking $booking): void
    {
        $this->analytics->track('booking_confirmed', [
            'booking_id' => $booking->id,
            'customer_id' => $booking->customer->id,
            'customer_type' => $booking->customer->type->value,
            'pass_type' => $booking->passType->value,
            'total_cents' => $booking->total()?->cents(),
            'payment_provider' => $booking->paymentReceipt()?->provider,
        ]);
    }
}
