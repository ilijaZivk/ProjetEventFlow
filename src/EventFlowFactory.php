<?php

declare(strict_types=1);

final class EventFlowFactory
{
    public static function bookingService(): BookingService
    {
        $logger = new ConsoleLogger();

        return new BookingService(
            new PriceCalculator(
                new VipTierDiscount(),
                new ThreeDayPassDiscount(),
            ),
            new PaymentGateways(
                new MonitoredPaymentGateway(new StripePaymentGateway(new StripeClient()), $logger),
                new MonitoredPaymentGateway(new PayFastPaymentGateway(new PayFastSdk()), $logger),
            ),
            new ConsoleBookingRepository(),
            new SendConfirmationEmail(new EmailService()),
            new AwardLoyaltyPoints(new LoyaltyService()),
            new TrackBookingConfirmed(new AnalyticsClient()),
            new SendConfirmationSms(new SmsClient()),
        );
    }
}
