<?php

declare(strict_types=1);

final class PaymentGateways
{
    /** @var array<string, PaymentGateway> */
    private array $gateways = [];

    public function __construct(PaymentGateway ...$gateways)
    {
        foreach ($gateways as $gateway) {
            $this->gateways[$gateway->name()] = $gateway;
        }
    }

    public function get(string $name): PaymentGateway
    {
        return $this->gateways[$name] ?? throw new RuntimeException('Unknown payment method');
    }
}
