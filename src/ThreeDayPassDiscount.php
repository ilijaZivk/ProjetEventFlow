<?php

declare(strict_types=1);

final class ThreeDayPassDiscount implements PricingRule
{
    private const DISCOUNT_CENTS = 2000;

    public function apply(Money $amount, Booking $booking): Money
    {
        if ($booking->passType !== PassType::ThreeDays) {
            return $amount;
        }

        return $amount->subtractOrZero(Money::fromCents(self::DISCOUNT_CENTS));
    }
}
