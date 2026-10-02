<?php

declare(strict_types=1);

interface PaymentGateway
{
    public function name(): string;

    /**
     * @throws PaymentFailedException
     */
    public function charge(Money $amount, string $reference): PaymentReceipt;
}
