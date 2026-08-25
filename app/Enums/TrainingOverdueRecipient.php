<?php

declare(strict_types=1);

namespace App\Enums;

/*
 * 受講期限切れ通知の宛先種別。同じ通知でも宛先によって本文の言い回しを変える。
 */
enum TrainingOverdueRecipient: string
{
    case Self = 'self';
    case Manager = 'manager';
    case Hr = 'hr';
}
