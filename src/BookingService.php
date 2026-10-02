<?php

declare(strict_types=1);

final class BookingService
{
    public function __construct(
        private readonly PriceCalculator $priceCalculator,
        private readonly PaymentGateways $paymentGateways
    ) {
    }

    public function confirm(Booking $booking, string $paymentMethod = 'stripe'): Money
    {
        $booking->assertCanBeConfirmed();

        $total = $this->priceCalculator->totalFor($booking);
        $gateway = $this->paymentGateways->get($paymentMethod);
        $receipt = $total->isZero()
            ? PaymentReceipt::noPaymentRequired()
            : $gateway->charge($total, "booking-{$booking->id}");

        $booking->markAsConfirmed($total, $receipt);

        echo "SQL INSERT booking={$booking->id} total={$total->format()} status={$booking->status()->value}" . PHP_EOL;

        $emailService = new EmailService();
        $emailService->sendConfirmation($booking->customer->email, $booking->id);

        return $total;
    }
}
