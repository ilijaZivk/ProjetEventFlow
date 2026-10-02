<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/TestRunner.php';

$tests = new TestRunner();

final class InMemoryLogger implements Logger
{
    /** @var array<int, array{level:string, message:string, context:array<string, mixed>}> */
    public array $entries = [];

    public function info(string $message, array $context = []): void
    {
        $this->entries[] = ['level' => 'info', 'message' => $message, 'context' => $context];
    }

    public function error(string $message, array $context = []): void
    {
        $this->entries[] = ['level' => 'error', 'message' => $message, 'context' => $context];
    }
}

final class FakePaymentGateway implements PaymentGateway
{
    public int $calls = 0;

    public function __construct(private readonly bool $fails = false)
    {
    }

    public function name(): string
    {
        return 'fake';
    }

    public function charge(Money $amount, string $reference): PaymentReceipt
    {
        $this->calls++;

        if ($this->fails) {
            throw PaymentFailedException::from('fake', 'refused');
        }

        return new PaymentReceipt('fake', "fake_{$reference}", $amount);
    }
}

function fakeClock(float ...$instants): Closure
{
    return static function () use (&$instants): float {
        return array_shift($instants);
    };
}

$logger = new InMemoryLogger();
$inner = new FakePaymentGateway();
$monitored = new MonitoredPaymentGateway($inner, $logger, fakeClock(10.0, 10.25));

$receipt = $monitored->charge(Money::fromEuros(143.82), 'booking-1001');

$tests->same('fake_booking-1001', $receipt->transactionId, 'receipt from the real gateway is returned unchanged');
$tests->same(1, $inner->calls, 'the wrapped gateway is charged exactly once');
$tests->same('fake', $monitored->name(), 'monitored gateway keeps the provider name');
$tests->same(1, count($logger->entries), 'one log entry per payment');
$tests->same('info', $logger->entries[0]['level'], 'success is logged as info');
$tests->same('payment.succeeded', $logger->entries[0]['message'], 'success is logged');
$tests->same('143.82', $logger->entries[0]['context']['amount'], 'requested amount is logged');
$tests->same(250.0, $logger->entries[0]['context']['duration_ms'], 'payment duration is measured');

$logger = new InMemoryLogger();
$monitored = new MonitoredPaymentGateway(new FakePaymentGateway(fails: true), $logger, fakeClock(1.0, 1.5));

$tests->throws(
    fn () => $monitored->charge(Money::fromEuros(20.0), 'booking-2'),
    'fake payment failed: refused',
    'failure is rethrown unchanged to the caller'
);
$tests->same('error', $logger->entries[0]['level'], 'failure is logged as error');
$tests->same('payment.failed', $logger->entries[0]['message'], 'failure is logged');
$tests->same('20.00', $logger->entries[0]['context']['amount'], 'requested amount is logged on failure');
$tests->same(500.0, $logger->entries[0]['context']['duration_ms'], 'duration is measured on failure');
$tests->same('fake payment failed: refused', $logger->entries[0]['context']['error'], 'failure reason is logged');

$logger = new InMemoryLogger();
(new MonitoredPaymentGateway(new PayFastPaymentGateway(new PayFastSdk()), $logger))->charge(Money::fromEuros(50.0), 'booking-3');
$tests->same('payfast', $logger->entries[0]['context']['provider'], 'payfast payments are monitored');

$logger = new InMemoryLogger();
(new MonitoredPaymentGateway(new StripePaymentGateway(new StripeClient()), $logger))->charge(Money::fromEuros(50.0), 'booking-4');
$tests->same('stripe', $logger->entries[0]['context']['provider'], 'stripe payments are monitored');

$tests->summary();
