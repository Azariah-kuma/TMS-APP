<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use App\Models\TrainingRequest;

it('研修に属する受講記録一覧を取得できる', function () {
    $training = Training::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    expect($training->enrollments()->pluck('id'))->toEqual(collect([$enrollment->id]));
});

it('研修に属する受講申請一覧を取得できる', function () {
    $training = Training::factory()->create();
    $request = TrainingRequest::factory()->create(['training_id' => $training->id]);

    expect($training->trainingRequests()->pluck('id'))->toEqual(collect([$request->id]));
});

it('position順にLesson一覧を取得できる', function () {
    $training = Training::factory()->create();
    $second = TrainingLesson::factory()->for($training)->create(['position' => 2]);
    $first = TrainingLesson::factory()->for($training)->create(['position' => 1]);

    expect($training->lessons()->pluck('id')->all())->toBe([$first->id, $second->id]);
});

it('対象者の制限がない研修は誰でも閲覧できる', function () {
    $training = Training::factory()->create();
    $employee = createEmployeeWithAssignment();

    expect($training->isVisibleTo($employee))->toBeTrue();
});

it('対象部署が一致する従業員は研修を閲覧できる', function () {
    $department = Department::factory()->create();
    $training = Training::factory()->create(['audience_department_id' => $department->id]);
    $employee = createEmployeeWithAssignment([], ['department_id' => $department->id]);

    expect($training->isVisibleTo($employee))->toBeTrue();
});

it('対象部署が一致しない従業員は研修を閲覧できない', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $training = Training::factory()->create(['audience_department_id' => $department->id]);
    $employee = createEmployeeWithAssignment([], ['department_id' => $otherDepartment->id]);

    expect($training->isVisibleTo($employee))->toBeFalse();
});

it('管理職向けの研修は管理職なら閲覧できる', function () {
    $training = Training::factory()->create(['audience_managers_only' => true]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment([], ['manager_id' => $manager->id]);

    expect($training->isVisibleTo($manager->fresh()))->toBeTrue();
});

it('管理職向けの研修は一般社員は閲覧できない', function () {
    $training = Training::factory()->create(['audience_managers_only' => true]);
    $employee = createEmployeeWithAssignment();

    expect($training->isVisibleTo($employee))->toBeFalse();
});

it('新入社員向けの研修は今年度入社の従業員なら閲覧できる', function () {
    $training = Training::factory()->create(['audience_new_hires_only' => true]);
    $employee = createEmployeeWithAssignment(['hired_at' => now()]);

    expect($training->isVisibleTo($employee))->toBeTrue();
});

it('新入社員向けの研修は前年度以前入社の従業員は閲覧できない', function () {
    $training = Training::factory()->create(['audience_new_hires_only' => true]);
    $employee = createEmployeeWithAssignment(['hired_at' => now()->subYears(3)]);

    expect($training->isVisibleTo($employee))->toBeFalse();
});

it('多段階承認が必要な研修は、対象部署でなくても管理職なら閲覧できる', function () {
    $training = Training::factory()->create(['requires_multistage_approval' => true, 'approval_stage_count' => 2]);
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment([], ['manager_id' => $manager->id]);

    expect($training->isVisibleTo($manager->fresh()))->toBeTrue();
});

it('多段階承認が必要な研修は、管理職でも対象者でもない一般社員は閲覧できない', function () {
    $training = Training::factory()->create(['requires_multistage_approval' => true, 'approval_stage_count' => 2]);
    $employee = createEmployeeWithAssignment();

    expect($training->isVisibleTo($employee))->toBeFalse();
});
