<?php

declare(strict_types=1);

final class PayFastPaymentGateway implements PaymentGateway
{
    private const CURRENCY = 'EUR';

    public function __construct(private readonly PayFastSdk $sdk)
    {
    }

    public function name(): string
    {
        return 'payfast';
    }

    public function charge(Money $amount, string $reference): PaymentReceipt
    {
        $response = $this->sdk->executePayment([
            'reference' => $reference,
            'amount_cents' => $amount->cents(),
            'currency' => self::CURRENCY,
        ]);

        if (!$response['success']) {
            throw PaymentFailedException::from($this->name(), 'payment refused');
        }

        return new PaymentReceipt($this->name(), $response['transaction_id'], $amount);
    }
}
