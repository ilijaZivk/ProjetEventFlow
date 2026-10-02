<?php

declare(strict_types=1);

final class StripePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly StripeClient $client)
    {
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function charge(Money $amount, string $reference): PaymentReceipt
    {
        try {
            $transactionId = $this->client->charge($amount->toFloat());
        } catch (RuntimeException $e) {
            throw PaymentFailedException::from($this->name(), $e->getMessage(), $e);
        }

        return new PaymentReceipt($this->name(), $transactionId, $amount);
    }
}
