<?php

declare(strict_types=1);

use App\Actions\Trainings\EnrollEmployeeInTrainingAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\EmployeeRetiredException;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Illuminate\Support\Facades\DB;

it('従業員を研修に「未着手」ステータスで受講登録する', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    $enrollment = (new EnrollEmployeeInTrainingAction)->execute($employee, $training);

    expect($enrollment->employee_id)->toBe($employee->id)
        ->and($enrollment->training_id)->toBe($training->id)
        ->and($enrollment->status)->toBe(TrainingEnrollmentStatus::NotStarted)
        ->and($enrollment->progress)->toBe(0);
});

it('同じ従業員を同じ研修に二重登録することは拒否される', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    (new EnrollEmployeeInTrainingAction)->execute($employee, $training);

    expect(fn () => (new EnrollEmployeeInTrainingAction)->execute($employee, $training))
        ->toThrow(AlreadyEnrolledException::class);
});

it('本物のデータベース競合状態が発生した場合もAlreadyEnrolledExceptionに変換される', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    // exists()チェックを通過した直後（Eloquentのcreatingイベント発火時点）に、
    // 別プロセスが同じ組み合わせで先に登録を完了させてしまった状況を単一コネクション内で再現する。
    TrainingEnrollment::creating(function () use ($employee, $training): void {
        DB::table('training_enrollments')->insert([
            'employee_id' => $employee->id,
            'training_id' => $training->id,
            'status' => TrainingEnrollmentStatus::NotStarted->value,
            'progress' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    try {
        expect(fn () => (new EnrollEmployeeInTrainingAction)->execute($employee, $training))
            ->toThrow(AlreadyEnrolledException::class);
    } finally {
        TrainingEnrollment::flushEventListeners();
    }
});

it('退職済みの従業員を研修に受講登録することは拒否される', function () {
    $employee = Employee::factory()->create(['retired_at' => now()->subDay()]);
    $training = Training::factory()->create();

    expect(fn () => (new EnrollEmployeeInTrainingAction)->execute($employee, $training))
        ->toThrow(EmployeeRetiredException::class);
});
