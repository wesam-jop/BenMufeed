<?php

namespace App\Enums;

enum StorefrontCustomerCreatedVia: string
{
    case TableQr = 'table_qr';
    case TakeawayOtp = 'takeaway_otp';
    case DeliveryOtp = 'delivery_otp';
}
