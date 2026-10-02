<?php

declare(strict_types=1);

final class EventFlowFactory
{
    public static function bookingService(): BookingService
    {
        return new BookingService(
            new PriceCalculator(
                new VipTierDiscount(),
                new ThreeDayPassDiscount(),
            ),
            new PaymentGateways(
                new StripePaymentGateway(new StripeClient()),
                new PayFastPaymentGateway(new PayFastSdk()),
            ),
        );
    }
}
