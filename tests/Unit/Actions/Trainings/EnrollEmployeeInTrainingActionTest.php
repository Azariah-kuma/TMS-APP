<?php

declare(strict_types=1);

use App\Actions\Trainings\EnrollEmployeeInTrainingAction;
use App\Enums\TrainingEnrollmentStatus;
use App\Exceptions\AlreadyEnrolledException;
use App\Models\Employee;
use App\Models\Training;

it('enrolls an employee in a training with default not-started status', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    $enrollment = (new EnrollEmployeeInTrainingAction)->execute($employee, $training);

    expect($enrollment->employee_id)->toBe($employee->id)
        ->and($enrollment->training_id)->toBe($training->id)
        ->and($enrollment->status)->toBe(TrainingEnrollmentStatus::NotStarted)
        ->and($enrollment->progress)->toBe(0);
});

it('rejects enrolling the same employee in the same training twice', function () {
    $employee = Employee::factory()->create();
    $training = Training::factory()->create();

    (new EnrollEmployeeInTrainingAction)->execute($employee, $training);

    expect(fn () => (new EnrollEmployeeInTrainingAction)->execute($employee, $training))
        ->toThrow(AlreadyEnrolledException::class);
});
