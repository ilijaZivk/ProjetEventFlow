<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new TestRunner();

function createBooking(
    CustomerType $customerType = CustomerType::Standard,
    PassType $passType = PassType::Day,
    float $price = 50.0,
    int $quantity = 1,
    ?string $phone = '0600000000',
    string $email = 'test@example.com'
): Booking {
    $customer = new Customer(1, $email, $phone, $customerType);
    $ticket = new Ticket('TEST', 'Ticket test', Money::fromEuros($price));
    $booking = new Booking(1, $customer, $passType);
    $booking->addItem(new BookingItem($ticket, $quantity));
    return $booking;
}

function confirmSilently(BookingService $service, Booking $booking, string $paymentMethod = 'stripe'): Money
{
    ob_start();
    try {
        return $service->confirm($booking, $paymentMethod);
    } finally {
        ob_end_clean();
    }
}

$service = EventFlowFactory::bookingService();

$standard = createBooking(CustomerType::Standard, PassType::Day, 50.0, 2);
$standardTotal = confirmSilently($service, $standard);
$tests->same(10000, $standardTotal->cents(), 'standard customer keeps initial total');
$tests->same(BookingStatus::Confirmed, $standard->status(), 'booking becomes confirmed');

$vip = createBooking(CustomerType::Vip, PassType::Day, 50.0, 2);
$vipTotal = confirmSilently($service, $vip);
$tests->same(9000, $vipTotal->cents(), 'VIP with 100 euros initial total gets 10 percent');

$threeDays = createBooking(CustomerType::Standard, PassType::ThreeDays, 60.0, 2);
$threeDaysTotal = confirmSilently($service, $threeDays);
$tests->same(10000, $threeDaysTotal->cents(), 'three day pass discount is now 20 euros');

$vipThreeDays = createBooking(CustomerType::Vip, PassType::ThreeDays, 50.0, 2);
$vipThreeDaysTotal = confirmSilently($service, $vipThreeDays);
$tests->same(7000, $vipThreeDaysTotal->cents(), 'VIP discount is applied before three day pass discount');

$floatDrift = createBooking(CustomerType::Vip, PassType::Day, 79.90, 2);
$floatDriftTotal = confirmSilently($service, $floatDrift);
$tests->same(14382, $floatDriftTotal->cents(), 'amounts are exact cents without float drift');

$cheapThreeDays = createBooking(CustomerType::Standard, PassType::ThreeDays, 5.0, 1);
$cheapTotal = confirmSilently($service, $cheapThreeDays);
$tests->same(0, $cheapTotal->cents(), 'final amount is never negative');
$tests->same(BookingStatus::Confirmed, $cheapThreeDays->status(), 'free booking is confirmed without payment');
$tests->same('none', $cheapThreeDays->paymentReceipt()->provider, 'free booking does not call any payment provider');

$emptyBooking = new Booking(1, new Customer(1, 'test@example.com'));
$tests->throws(
    fn () => confirmSilently($service, $emptyBooking),
    'Empty booking',
    'empty booking is rejected'
);

$tests->throws(
    fn () => confirmSilently($service, createBooking(email: 'pas-un-email')),
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

$alreadyConfirmed = createBooking();
confirmSilently($service, $alreadyConfirmed);
$tests->throws(
    fn () => confirmSilently($service, $alreadyConfirmed),
    'Booking already confirmed',
    'a confirmed booking cannot be paid twice'
);

$tests->summary();
