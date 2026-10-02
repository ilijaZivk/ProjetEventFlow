<?php

declare(strict_types=1);

final class MonitoredPaymentGateway implements PaymentGateway
{
    /** @var Closure(): float */
    private Closure $clock;

    public function __construct(
        private readonly PaymentGateway $inner,
        private readonly Logger $logger,
        ?Closure $clock = null
    ) {
        $this->clock = $clock ?? static fn (): float => hrtime(true) / 1e9;
    }

    public function name(): string
    {
        return $this->inner->name();
    }

    public function charge(Money $amount, string $reference): PaymentReceipt
    {
        $context = [
            'provider' => $this->inner->name(),
            'reference' => $reference,
            'amount' => $amount->format(),
        ];
        $startedAt = ($this->clock)();

        try {
            $receipt = $this->inner->charge($amount, $reference);
        } catch (Throwable $e) {
            $this->logger->error('payment.failed', $context + [
                'duration_ms' => $this->elapsedMs($startedAt),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $this->logger->info('payment.succeeded', $context + [
            'duration_ms' => $this->elapsedMs($startedAt),
            'transaction_id' => $receipt->transactionId,
        ]);

        return $receipt;
    }

    private function elapsedMs(float $startedAt): float
    {
        return round((($this->clock)() - $startedAt) * 1000, 3);
    }
}
