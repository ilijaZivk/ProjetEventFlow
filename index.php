<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$customer = new Customer(
    id: 42,
    email: 'lea@example.com',
    phone: '0612345678',
    type: CustomerType::Vip
);

$dayTicket = new Ticket(
    code: 'DAY-1',
    label: 'Pass Jour 1',
    price: Money::fromEuros(79.90)
);

$booking = new Booking(
    id: 1001,
    customer: $customer,
    passType: PassType::Day
);

$booking->addItem(new BookingItem($dayTicket, 2));

$service = EventFlowFactory::bookingService();
$total = $service->confirm($booking, 'stripe');

echo 'PAYMENT ' . $booking->paymentReceipt()->transactionId . PHP_EOL;
echo 'TOTAL FINAL: ' . $total->format() . PHP_EOL;
