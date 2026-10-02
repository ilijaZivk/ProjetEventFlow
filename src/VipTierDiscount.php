<?php

declare(strict_types=1);

final class VipTierDiscount implements PricingRule
{
    private const TIERS_FROM_CENTS = [
        30000 => 15,
        10000 => 10,
        0 => 5,
    ];

    public function apply(Money $amount, Booking $booking): Money
    {
        if (!$booking->customer->isVip()) {
            return $amount;
        }

        return $amount->applyDiscountPercent($this->discountPercentFor($booking->subtotal()));
    }

    private function discountPercentFor(Money $initialTotal): int
    {
        foreach (self::TIERS_FROM_CENTS as $thresholdCents => $percent) {
            if ($initialTotal->cents() >= $thresholdCents) {
                return $percent;
            }
        }

        return 0;
    }
}
