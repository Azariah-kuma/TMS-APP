<?php

declare(strict_types=1);

namespace App\Enums;

/*
 * 研修受講ステータス
 */
enum TrainingEnrollmentStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
}
