<?php

declare(strict_types=1);

final class Booking
{
    /** @var BookingItem[] */
    private array $items = [];
    private BookingStatus $status = BookingStatus::Pending;
    private ?Money $total = null;
    private ?PaymentReceipt $paymentReceipt = null;

    public function __construct(
        public readonly int $id,
        public readonly Customer $customer,
        public readonly PassType $passType = PassType::Day
    ) {
    }

    public function addItem(BookingItem $item): void
    {
        $this->items[] = $item;
    }

    /** @return BookingItem[] */
    public function items(): array
    {
        return $this->items;
    }

    public function status(): BookingStatus
    {
        return $this->status;
    }

    public function total(): ?Money
    {
        return $this->total;
    }

    public function paymentReceipt(): ?PaymentReceipt
    {
        return $this->paymentReceipt;
    }

    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::Confirmed;
    }

    public function subtotal(): Money
    {
        $total = Money::zero();
        foreach ($this->items as $item) {
            $total = $total->add($item->lineTotal());
        }

        return $total;
    }

    public function assertCanBeConfirmed(): void
    {
        if ($this->items === []) {
            throw new RuntimeException('Empty booking');
        }

        if (!$this->customer->hasValidEmail()) {
            throw new RuntimeException('Invalid email');
        }

        foreach ($this->items as $item) {
            if (!$item->hasValidQuantity()) {
                throw new RuntimeException('Invalid quantity');
            }
        }

        if ($this->isConfirmed()) {
            throw new RuntimeException('Booking already confirmed');
        }
    }

    public function markAsConfirmed(Money $total, PaymentReceipt $receipt): void
    {
        $this->assertCanBeConfirmed();
        $this->status = BookingStatus::Confirmed;
        $this->total = $total;
        $this->paymentReceipt = $receipt;
    }
}
