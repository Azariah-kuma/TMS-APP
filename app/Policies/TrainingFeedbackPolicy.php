<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TrainingEnrollment;
use App\Models\User;

/*
 * 研修効果測定（アンケート・テストの提出）のポリシークラス。
 */
final class TrainingFeedbackPolicy
{
    /** 提出できるのは受講記録の本人のみ（人事はGate::beforeで別途許可される）。 */
    public function create(User $user, TrainingEnrollment $enrollment): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->is($enrollment->employee);
    }
}
