<?php

declare(strict_types=1);

namespace App\Enums;

/*
 * 多段階承認における、各段階の決裁結果
 */
enum TrainingRequestApprovalStageStatus: string
{
    case Approved = 'approved';
    case Rejected = 'rejected';
}
