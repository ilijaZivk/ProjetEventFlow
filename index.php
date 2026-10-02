<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$service = EventFlowFactory::bookingService();

$lea = new Customer(id: 42, email: 'lea@example.com', phone: '0612345678', type: CustomerType::Vip);
$dayTicket = new Ticket(code: 'DAY-1', label: 'Pass Jour 1', price: Money::fromEuros(79.90));

$booking = new Booking(id: 1001, customer: $lea, passType: PassType::Day);
$booking->addItem(new BookingItem($dayTicket, 2));

$total = $service->confirm($booking, 'stripe');
echo 'TOTAL FINAL: ' . $total->format() . PHP_EOL . PHP_EOL;

$marc = new Customer(id: 43, email: 'marc@example.com');
$threeDayTicket = new Ticket(code: '3D', label: 'Pass 3 jours', price: Money::fromEuros(199.00));

$booking = new Booking(id: 1002, customer: $marc, passType: PassType::ThreeDays);
$booking->addItem(new BookingItem($threeDayTicket, 1));

$total = $service->confirm($booking, 'payfast');
echo 'TOTAL FINAL: ' . $total->format() . PHP_EOL;
