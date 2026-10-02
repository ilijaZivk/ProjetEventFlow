<?php

declare(strict_types=1);

final class SendConfirmationEmail implements BookingConfirmedListener
{
    public function __construct(private readonly EmailService $emailService)
    {
    }

    public function onBookingConfirmed(Booking $booking): void
    {
        $this->emailService->sendConfirmation($booking->customer->email, $booking->id);
    }
}
