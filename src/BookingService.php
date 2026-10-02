<?php

declare(strict_types=1);

final class BookingService
{
    private const LEGACY_VIP_DISCOUNT_PERCENT = 10;
    private const LEGACY_THREE_DAYS_DISCOUNT_CENTS = 1000;

    public function __construct(private readonly PaymentGateways $paymentGateways)
    {
    }

    public function confirm(Booking $booking, string $paymentMethod = 'stripe'): Money
    {
        $booking->assertCanBeConfirmed();

        $total = $this->calculateTotal($booking);
        $receipt = $this->paymentGateways->get($paymentMethod)->charge($total, "booking-{$booking->id}");

        $booking->markAsConfirmed($total, $receipt);

        echo "SQL INSERT booking={$booking->id} total={$total->format()} status={$booking->status()->value}" . PHP_EOL;

        $emailService = new EmailService();
        $emailService->sendConfirmation($booking->customer->email, $booking->id);

        return $total;
    }

    private function calculateTotal(Booking $booking): Money
    {
        $total = $booking->subtotal();

        if ($booking->customer->isVip()) {
            $total = $total->applyDiscountPercent(self::LEGACY_VIP_DISCOUNT_PERCENT);
        }

        if ($booking->passType === PassType::ThreeDays) {
            $total = $total->subtractOrZero(Money::fromCents(self::LEGACY_THREE_DAYS_DISCOUNT_CENTS));
        }

        return $total;
    }
}
