<?php

declare(strict_types=1);

interface PricingRule
{
    public function apply(Money $amount, Booking $booking): Money;
}
