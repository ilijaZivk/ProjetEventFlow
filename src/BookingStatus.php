<?php

declare(strict_types=1);

enum BookingStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
}