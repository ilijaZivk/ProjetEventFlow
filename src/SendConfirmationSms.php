<?php

declare(strict_types=1);

final class SendConfirmationSms implements BookingConfirmedListener
{
    public function __construct(private readonly SmsClient $smsClient)
    {
    }

    public function onBookingConfirmed(Booking $booking): void
    {
        if (!$booking->customer->hasPhone()) {
            return;
        }

        $this->smsClient->send(
            (string) $booking->customer->phone,
            "Votre réservation {$booking->id} est confirmée."
        );
    }
}
