<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\EmployeeAssignment;
use App\Models\Training;

it('部署に属する配属一覧を取得できる', function () {
    $department = Department::factory()->create();
    $assignment = EmployeeAssignment::factory()->create(['department_id' => $department->id]);

    expect($department->assignments()->pluck('id'))->toEqual(collect([$assignment->id]));
});

it('新規作成した部署には配属が存在しない', function () {
    $department = Department::factory()->create();

    expect($department->assignments()->count())->toBe(0);
});

it('この部署を閲覧対象部署に指定している研修一覧を取得できる', function () {
    $department = Department::factory()->create();
    $training = Training::factory()->create(['audience_department_id' => $department->id]);

    expect($department->trainings()->pluck('id'))->toEqual(collect([$training->id]));
});
