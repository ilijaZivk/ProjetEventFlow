<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new TestRunner();

function createBooking(
    string $customerType = 'standard',
    string $passType = 'day',
    float $price = 50.0,
    int $quantity = 1,
    ?string $phone = '0600000000'
): Booking {
    $customer = new Customer(1, 'test@example.com', $phone, $customerType);
    $ticket = new Ticket('TEST', 'Ticket test', $price);
    $booking = new Booking(1, $customer, $passType);
    $booking->addItem(new BookingItem($ticket, $quantity));
    return $booking;
}

function confirmSilently(BookingService $service, Booking $booking, string $paymentMethod = 'stripe'): float
{
    ob_start();
    try {
        return $service->confirm($booking, $paymentMethod);
    } finally {
        ob_end_clean();
    }
}

$service = new BookingService();

$standard = createBooking('standard', 'day', 50.0, 2);
$standardTotal = confirmSilently($service, $standard);
$tests->near(100.0, $standardTotal, 'standard customer keeps initial total');
$tests->same('confirmed', $standard->status, 'booking becomes confirmed');

$vip = createBooking('vip', 'day', 50.0, 2);
$vipTotal = confirmSilently($service, $vip);
$tests->near(90.0, $vipTotal, 'legacy VIP rule gives 10 percent discount');

$threeDays = createBooking('standard', '3days', 60.0, 2);
$threeDaysTotal = confirmSilently($service, $threeDays);
$tests->near(110.0, $threeDaysTotal, 'legacy three day pass discount is 10 euros');

$emptyBooking = new Booking(1, new Customer(1, 'test@example.com'));
$tests->throws(
    fn () => confirmSilently($service, $emptyBooking),
    'Empty booking',
    'empty booking is rejected'
);

$badEmail = new Booking(1, new Customer(1, 'pas-un-email'));
$badEmail->addItem(new BookingItem(new Ticket('TEST', 'Ticket test', 50.0), 1));
$tests->throws(
    fn () => confirmSilently($service, $badEmail),
    'Invalid email',
    'invalid email is rejected'
);

$tests->throws(
    fn () => confirmSilently($service, createBooking(quantity: 0)),
    'Invalid quantity',
    'zero quantity is rejected'
);

$tests->throws(
    fn () => confirmSilently($service, createBooking(), 'paypal'),
    'Unknown payment method',
    'unknown payment method is rejected'
);

$tests->summary();