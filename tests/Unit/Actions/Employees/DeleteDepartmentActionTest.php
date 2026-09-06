<?php

declare(strict_types=1);

use App\Actions\Employees\DeleteDepartmentAction;
use App\Exceptions\DepartmentInUseException;
use App\Models\Department;
use App\Models\EmployeeAssignment;
use App\Models\Training;

it('使われていない部署を削除する', function () {
    $department = Department::factory()->create();

    (new DeleteDepartmentAction)->execute($department);

    expect(Department::find($department->id))->toBeNull();
});

it('配属履歴で使用されている部署は削除できない', function () {
    $department = Department::factory()->create();
    EmployeeAssignment::factory()->create(['department_id' => $department->id]);

    expect(fn () => (new DeleteDepartmentAction)->execute($department))
        ->toThrow(DepartmentInUseException::class);

    expect(Department::find($department->id))->not->toBeNull();
});

it('研修の閲覧対象部署として使用されている部署は削除できない', function () {
    $department = Department::factory()->create();
    Training::factory()->create(['audience_department_id' => $department->id]);

    expect(fn () => (new DeleteDepartmentAction)->execute($department))
        ->toThrow(DepartmentInUseException::class);

    expect(Department::find($department->id))->not->toBeNull();
});
