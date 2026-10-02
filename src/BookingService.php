<?php

declare(strict_types=1);

final class BookingService
{
    /** @var BookingConfirmedListener[] */
    private array $listeners;

    public function __construct(
        private readonly PriceCalculator $priceCalculator,
        private readonly PaymentGateways $paymentGateways,
        private readonly BookingRepository $bookings,
        BookingConfirmedListener ...$listeners
    ) {
        $this->listeners = $listeners;
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
        $this->bookings->save($booking);

        foreach ($this->listeners as $listener) {
            $listener->onBookingConfirmed($booking);
        }

        return $total;
    }
}
