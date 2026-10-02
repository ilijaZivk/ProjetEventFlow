<?php

declare(strict_types=1);

final class PaymentFailedException extends RuntimeException
{
    public static function from(string $provider, string $reason, ?Throwable $previous = null): self
    {
        return new self("{$provider} payment failed: {$reason}", 0, $previous);
    }
}
