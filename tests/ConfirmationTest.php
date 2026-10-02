<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new TestRunner();

final class InMemoryBookingRepository implements BookingRepository
{
    /** @var Booking[] */
    public array $saved = [];

    public function save(Booking $booking): void
    {
        $this->saved[] = $booking;
    }
}

final class FakePaymentGateway implements PaymentGateway
{
    public function __construct(private readonly bool $fails = false)
    {
    }

    public function name(): string
    {
        return 'fake';
    }

    public function charge(Money $amount, string $reference): PaymentReceipt
    {
        if ($this->fails) {
            throw PaymentFailedException::from('fake', 'refused');
        }

        return new PaymentReceipt('fake', "fake_{$reference}", $amount);
    }
}

final class RecordingListener implements BookingConfirmedListener
{
    /** @var string[] */
    public static array $calls = [];

    public function __construct(private readonly string $name)
    {
    }

    public function onBookingConfirmed(Booking $booking): void
    {
        self::$calls[] = "{$this->name}:{$booking->id}";
    }
}

function confirmationBooking(?string $phone = '0600000000', PassType $passType = PassType::Day, float $price = 79.90): Booking
{
    $booking = new Booking(1, new Customer(7, 'client@example.com', $phone), $passType);
    $booking->addItem(new BookingItem(new Ticket('T', 'Ticket', Money::fromEuros($price)), 2));

    return $booking;
}

function captureOutput(callable $call): string
{
    ob_start();
    try {
        $call();
    } finally {
        $output = (string) ob_get_clean();
    }

    return $output;
}

$repository = new InMemoryBookingRepository();
$service = new BookingService(
    new PriceCalculator(),
    new PaymentGateways(new FakePaymentGateway()),
    $repository,
    new RecordingListener('first'),
    new RecordingListener('second'),
);
$service->confirm(confirmationBooking(), 'fake');
$tests->same(['first:1', 'second:1'], RecordingListener::$calls, 'every listener is notified once, in order');
$tests->same(1, count($repository->saved), 'booking is saved before listeners run');

RecordingListener::$calls = [];
$repository = new InMemoryBookingRepository();
$failingService = new BookingService(
    new PriceCalculator(),
    new PaymentGateways(new FakePaymentGateway(fails: true)),
    $repository,
    new RecordingListener('only'),
);
try {
    $failingService->confirm(confirmationBooking(), 'fake');
} catch (PaymentFailedException) {
}
$tests->same([], RecordingListener::$calls, 'no listener is notified when payment fails');
$tests->same([], $repository->saved, 'booking is not saved when payment fails');

$output = captureOutput(fn () => EventFlowFactory::bookingService()->confirm(confirmationBooking(), 'stripe'));
$tests->same(true, str_contains($output, 'EMAIL client@example.com: booking 1 confirmed'), 'confirmation email is sent');
$tests->same(true, str_contains($output, 'LOYALTY customer=7 points=159'), 'loyalty points: one per whole euro paid');
$tests->same(true, str_contains($output, 'ANALYTICS booking_confirmed'), 'booking is sent to analytics');
$tests->same(true, str_contains($output, '"total_cents":15980'), 'analytics receives the final amount');
$tests->same(true, str_contains($output, 'SMS 0600000000:'), 'SMS is sent when the customer has a phone number');

$output = captureOutput(fn () => EventFlowFactory::bookingService()->confirm(confirmationBooking(phone: null), 'stripe'));
$tests->same(false, str_contains($output, 'SMS'), 'no SMS without phone number');
$tests->same(true, str_contains($output, 'EMAIL'), 'other reactions still run without phone number');

$output = captureOutput(fn () => (new SendConfirmationSms(new SmsClient()))->onBookingConfirmed(confirmationBooking(phone: '  ')));
$tests->same('', $output, 'blank phone number is treated as no phone');

$output = captureOutput(fn () => EventFlowFactory::bookingService()->confirm(confirmationBooking(passType: PassType::ThreeDays, price: 5.0), 'stripe'));
$tests->same(false, str_contains($output, 'LOYALTY'), 'free booking earns no loyalty points');

$tests->summary();
