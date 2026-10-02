<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new TestRunner();

function paymentBooking(int $id = 1): Booking
{
    $booking = new Booking($id, new Customer(7, 'client@example.com'));
    $booking->addItem(new BookingItem(new Ticket('T', 'Ticket', Money::fromEuros(40.0)), 2));

    return $booking;
}

function confirmQuietly(BookingService $service, Booking $booking, string $paymentMethod): Money
{
    ob_start();
    try {
        return $service->confirm($booking, $paymentMethod);
    } finally {
        ob_end_clean();
    }
}

$stripe = new StripePaymentGateway(new StripeClient());
$receipt = $stripe->charge(Money::fromEuros(159.80), 'booking-1');
$tests->same('stripe', $receipt->provider, 'stripe receipt names its provider');
$tests->same('stripe_159.80', $receipt->transactionId, 'stripe receipt keeps the stripe transaction id');
$tests->throws(
    fn () => $stripe->charge(Money::zero(), 'booking-1'),
    'stripe payment failed: Invalid amount',
    'stripe error becomes PaymentFailedException'
);

$payfast = new PayFastPaymentGateway(new PayFastSdk());
$receipt = $payfast->charge(Money::fromEuros(159.80), 'booking-1001');
$tests->same('payfast', $receipt->provider, 'payfast receipt names its provider');
$tests->same('payfast_booking-1001', $receipt->transactionId, 'payfast receives the booking reference');
$tests->same(15980, $receipt->amount->cents(), 'payfast is charged the exact amount in cents');
$tests->throws(
    fn () => $payfast->charge(Money::zero(), 'booking-1'),
    'payfast payment failed: payment refused',
    'payfast refusal becomes PaymentFailedException'
);

$service = EventFlowFactory::bookingService();

$viaStripe = paymentBooking();
confirmQuietly($service, $viaStripe, 'stripe');
$tests->same('stripe', $viaStripe->paymentReceipt()->provider, 'booking can be paid with stripe');

$viaPayFast = paymentBooking();
confirmQuietly($service, $viaPayFast, 'payfast');
$tests->same('payfast', $viaPayFast->paymentReceipt()->provider, 'booking can be paid with payfast');
$tests->same(BookingStatus::Confirmed, $viaPayFast->status(), 'payfast booking becomes confirmed');

$tests->throws(
    fn () => (new PaymentGateways())->get('stripe'),
    'Unknown payment method',
    'a payment method must be registered to be used'
);

$tests->summary();
