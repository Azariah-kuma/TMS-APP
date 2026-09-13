<?php

declare(strict_types=1);

use App\Models\TrainingEnrollment;
use App\Models\TrainingFeedback;
use App\Models\User;

it('受講記録の本人はフィードバックを提出できる', function () {
    $employee = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $employee->id]);

    expect($employee->user->can('create', [TrainingFeedback::class, $enrollment]))->toBeTrue();
});

it('本人以外はフィードバックを提出できない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();
    $enrollment = TrainingEnrollment::factory()->create(['employee_id' => $other->id]);

    expect($employee->user->can('create', [TrainingFeedback::class, $enrollment]))->toBeFalse();
});

it('従業員レコードのないユーザーはフィードバックを提出できない', function () {
    $user = User::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create();

    expect($user->can('create', [TrainingFeedback::class, $enrollment]))->toBeFalse();
});
