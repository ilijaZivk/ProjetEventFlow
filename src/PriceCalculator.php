<?php

declare(strict_types=1);

final class PriceCalculator
{
    /** @var PricingRule[] */
    private array $rules;

    public function __construct(PricingRule ...$rules)
    {
        $this->rules = $rules;
    }

    public function totalFor(Booking $booking): Money
    {
        $amount = $booking->subtotal();

        foreach ($this->rules as $rule) {
            $amount = $rule->apply($amount, $booking);
        }

        return $amount;
    }
}
