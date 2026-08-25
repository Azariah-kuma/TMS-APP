<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Enums\EmployeeRole;
use App\Enums\TrainingEnrollmentStatus;
use App\Enums\TrainingOverdueRecipient;
use App\Models\Employee;
use App\Models\TrainingEnrollment;
use App\Notifications\TrainingOverdueNotification;
use Illuminate\Support\Carbon;

final class SendTrainingOverdueNoticesAction
{
    /**
     * 受講期限が昨日で、まだ完了していない受講記録について、本人・現在の上司・全人事に
     * 期限切れを知らせる。「昨日」だけを対象にすることで、同じ受講記録に何度も送らず
     * 期限切れになった日に1回だけ送る。
     *
     * @return int 通知を送った件数（本人・上司・人事の合計）
     */
    public function execute(): int
    {
        $enrollments = TrainingEnrollment::query()
            ->whereDate('due_at', Carbon::yesterday())
            ->where('status', '!=', TrainingEnrollmentStatus::Completed)
            ->with(['employee.user', 'employee.currentAssignment.manager.user', 'training'])
            ->get();

        if ($enrollments->isEmpty()) {
            return 0;
        }

        $hrEmployees = Employee::query()
            ->where('role', EmployeeRole::Hr)
            ->whereNull('retired_at')
            ->with('user')
            ->get();

        $sent = 0;

        foreach ($enrollments as $enrollment) {
            $enrollment->employee->user->notify(
                new TrainingOverdueNotification($enrollment, TrainingOverdueRecipient::Self),
            );
            $sent++;

            $manager = $enrollment->employee->currentAssignment?->manager;
            if ($manager !== null) {
                $manager->user->notify(new TrainingOverdueNotification($enrollment, TrainingOverdueRecipient::Manager));
                $sent++;
            }

            foreach ($hrEmployees as $hr) {
                $hr->user->notify(new TrainingOverdueNotification($enrollment, TrainingOverdueRecipient::Hr));
                $sent++;
            }
        }

        return $sent;
    }
}
