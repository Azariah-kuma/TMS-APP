<?php

declare(strict_types=1);

use App\Models\Department;
use App\Models\DepartmentBudget;

it('紐づく部署を取得できる', function () {
    $department = Department::factory()->create();
    $budget = DepartmentBudget::factory()->create(['department_id' => $department->id]);

    expect($budget->department->is($department))->toBeTrue();
});
