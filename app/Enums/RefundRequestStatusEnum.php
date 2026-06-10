<?php

namespace App\Enums;

enum RefundRequestStatusEnum: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Declined = 'declined';
    case AutoDeclined = 'auto_declined';
}
