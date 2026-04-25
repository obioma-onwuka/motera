<?php

namespace App\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCESSFUL = 'successful';
    case FAILED = 'failed';
    case REVERSED = 'reversed';
    case CANCELLED = 'cancelled';
}
