<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\EmployeeAssignment;

it('部署に属する配属一覧を取得できる', function () {
    $department = Department::factory()->create();
    $assignment = EmployeeAssignment::factory()->create(['department_id' => $department->id]);

    expect($department->assignments()->pluck('id'))->toEqual(collect([$assignment->id]));
});

it('新規作成した部署には配属が存在しない', function () {
    $department = Department::factory()->create();

    expect($department->assignments()->count())->toBe(0);
});
