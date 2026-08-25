<?php

declare(strict_types=1);

use App\Actions\Trainings\ApproveTrainingRequestAction;
use App\Enums\TrainingRequestStatus;
use App\Exceptions\EmployeeRetiredException;
use App\Exceptions\TrainingRequestNotPendingException;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingRequest;

it('申請を承認し、受講記録を作成する', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();
    $manager = Employee::factory()->create();
    $request = TrainingRequest::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
        'due_at' => '2026-06-01',
    ]);

    $approved = app(ApproveTrainingRequestAction::class)->execute($request, $manager);

    expect($approved->status)->toBe(TrainingRequestStatus::Approved)
        ->and($approved->decided_by_employee_id)->toBe($manager->id)
        ->and($approved->decided_at)->not->toBeNull()
        ->and($approved->training_enrollment_id)->not->toBeNull();

    $enrollment = TrainingEnrollment::find($approved->training_enrollment_id);
    expect($enrollment->employee_id)->toBe($employee->id)
        ->and($enrollment->training_id)->toBe($training->id)
        ->and($enrollment->due_at->toDateString())->toBe('2026-06-01');
});

it('承認待ちでない申請は承認できない', function () {
    $request = TrainingRequest::factory()->approved()->create();

    expect(fn () => app(ApproveTrainingRequestAction::class)->execute($request, Employee::factory()->create()))
        ->toThrow(TrainingRequestNotPendingException::class);
});

it('承認する頃には退職済みだった場合は承認できず、申請は承認待ちのままになる', function () {
    $employee = Employee::factory()->create(['retired_at' => now()->subDay()]);
    $request = TrainingRequest::factory()->create(['employee_id' => $employee->id]);

    expect(fn () => app(ApproveTrainingRequestAction::class)->execute($request, Employee::factory()->create()))
        ->toThrow(EmployeeRetiredException::class);

    expect($request->fresh()->status)->toBe(TrainingRequestStatus::Pending);
});

it('別ルートで既に受講登録済みだった場合も、エラーにせず既存の受講記録に紐付けて承認扱いにする', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();
    $manager = Employee::factory()->create();

    $existingEnrollment = TrainingEnrollment::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);
    $request = TrainingRequest::factory()->create([
        'employee_id' => $employee->id,
        'training_id' => $training->id,
    ]);

    $approved = app(ApproveTrainingRequestAction::class)->execute($request, $manager);

    expect($approved->status)->toBe(TrainingRequestStatus::Approved)
        ->and($approved->training_enrollment_id)->toBe($existingEnrollment->id)
        ->and(TrainingEnrollment::where('employee_id', $employee->id)->where('training_id', $training->id)->count())->toBe(1);
});
