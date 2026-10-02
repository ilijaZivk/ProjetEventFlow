<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new TestRunner();

function pricedBooking(
    CustomerType $customerType = CustomerType::Standard,
    PassType $passType = PassType::Day,
    float $price = 50.0,
    int $quantity = 1
): Booking {
    $booking = new Booking(1, new Customer(1, 'client@example.com', null, $customerType), $passType);
    $booking->addItem(new BookingItem(new Ticket('T', 'Ticket', Money::fromEuros($price)), $quantity));

    return $booking;
}

$calculator = new PriceCalculator(new VipTierDiscount(), new ThreeDayPassDiscount());
$total = static fn (Booking $booking): int => $calculator->totalFor($booking)->cents();

$vip = CustomerType::Vip;
$threeDays = PassType::ThreeDays;

$tests->same(30000, $total(pricedBooking(price: 300.0)), 'standard customer gets no status discount');

$tests->same(9499, $total(pricedBooking($vip, price: 99.99)), 'VIP under 100 euros gets 5 percent');
$tests->same(9000, $total(pricedBooking($vip, price: 100.00)), 'VIP at exactly 100 euros gets 10 percent');
$tests->same(26999, $total(pricedBooking($vip, price: 299.99)), 'VIP at 299.99 euros still gets 10 percent');
$tests->same(25500, $total(pricedBooking($vip, price: 300.00)), 'VIP at exactly 300 euros gets 15 percent');
$tests->same(42500, $total(pricedBooking($vip, price: 250.0, quantity: 2)), 'VIP tier uses the whole booking total');

$tests->same(10000, $total(pricedBooking(passType: $threeDays, price: 60.0, quantity: 2)), 'three day pass removes 20 euros');
$tests->same(23500, $total(pricedBooking($vip, $threeDays, price: 300.0)), 'three day discount applies after VIP discount');
$tests->same(0, $total(pricedBooking(passType: $threeDays, price: 15.0)), 'final amount cannot go below zero');

$tests->same(5000, (new VipTierDiscount())->apply(Money::fromEuros(50.0), pricedBooking())->cents(), 'VIP discount ignores standard customers');
$tests->same(5000, (new ThreeDayPassDiscount())->apply(Money::fromEuros(50.0), pricedBooking())->cents(), 'three day discount ignores day passes');

$tests->summary();
