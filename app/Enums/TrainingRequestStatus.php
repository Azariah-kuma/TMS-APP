<?php

declare(strict_types=1);

namespace App\Enums;

/*
 * 研修受講申請のステータス
 */
enum TrainingRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
