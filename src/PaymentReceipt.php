<?php

declare(strict_types=1);

final class PaymentReceipt
{
    public function __construct(
        public readonly string $provider,
        public readonly string $transactionId,
        public readonly Money $amount
    ) {
    }

    public static function noPaymentRequired(): self
    {
        return new self('none', '', Money::zero());
    }
}
