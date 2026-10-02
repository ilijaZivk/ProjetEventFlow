<?php

declare(strict_types=1);

final class Customer
{
    public function __construct(
        public readonly int $id,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly CustomerType $type = CustomerType::Standard
    ) {
    }

    public function isVip(): bool
    {
        return $this->type === CustomerType::Vip;
    }

    public function hasValidEmail(): bool
    {
        return filter_var($this->email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function hasPhone(): bool
    {
        return $this->phone !== null && trim($this->phone) !== '';
    }
}