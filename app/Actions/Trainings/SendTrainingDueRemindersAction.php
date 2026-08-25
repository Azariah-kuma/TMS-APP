<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\TrainingEnrollment;
use App\Notifications\TrainingDueReminderNotification;
use Illuminate\Support\Carbon;

final class SendTrainingDueRemindersAction
{
    /**
     * 受講期限が明日で、まだ完了していない受講記録の本人にリマインドメールを送る。
     *
     * @return int 通知を送った件数
     */
    public function execute(): int
    {
        $enrollments = TrainingEnrollment::query()
            ->whereDate('due_at', Carbon::tomorrow())
            ->where('status', '!=', TrainingEnrollmentStatus::Completed)
            ->with(['employee.user', 'training'])
            ->get();

        foreach ($enrollments as $enrollment) {
            $enrollment->employee->user->notify(new TrainingDueReminderNotification($enrollment));
        }

        return $enrollments->count();
    }
}
