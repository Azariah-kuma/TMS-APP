<?php

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Unit');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

/**
 * 部署・役職を割り当てた（＝現在の割り当てを持つ）従業員を作成する。
 *
 * $assignmentAttributes に manager_id を渡すことで、上司つきの従業員も作成できる。
 */
function createEmployeeWithAssignment(
    array $employeeAttributes = [],
    array $assignmentAttributes = [],
): Employee {
    $employee = Employee::factory()->create($employeeAttributes);

    EmployeeAssignment::factory()->create(array_merge([
        'employee_id' => $employee->id,
        'department_id' => Department::factory(),
        'position_id' => Position::factory(),
    ], $assignmentAttributes));

    return $employee->fresh();
}
