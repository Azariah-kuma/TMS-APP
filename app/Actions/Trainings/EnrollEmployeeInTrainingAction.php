<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Actions\Employees\Concerns\GuardsAgainstRetiredEmployees;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\AlreadyEnrolledException;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;

final class EnrollEmployeeInTrainingAction
{
    use GuardsAgainstRetiredEmployees;

    public function execute(Employee $employee, Training $training, ?Carbon $dueAt = null): TrainingEnrollment
    {
        $this->assertNotRetired($employee, '退職済みの従業員を研修に登録することはできません。');

        $alreadyEnrolled = $employee->trainingEnrollments()
            ->where('training_id', $training->id)
            ->exists();

        if ($alreadyEnrolled) {
            throw new AlreadyEnrolledException('この従業員は既にこの研修に登録されています。');
        }

        // 最終的な一意性は training_enrollments テーブルのユニーク制約が保証する。
        // 制約に違反した場合はここで捕捉して同じドメイン例外に変換する。
        try {
            return $employee->trainingEnrollments()->create([
                'training_id' => $training->id,
                'status' => TrainingEnrollmentStatus::NotStarted,
                'progress' => 0,
                'due_at' => $dueAt,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw new AlreadyEnrolledException('この従業員は既にこの研修に登録されています。');
        }
    }
}
