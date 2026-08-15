<?php

declare(strict_types=1);

use App\Actions\Employees\TransferEmployeeAction;
use App\Exceptions\InvalidAssignmentPeriodException;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use Illuminate\Support\Carbon;

it('closes the previous assignment and opens a new one on transfer', function () {
    $employee = Employee::factory()->create();

    $original = EmployeeAssignment::factory()->create([
        'employee_id' => $employee->id,
        'started_at' => '2024-01-01',
        'ended_at' => null,
    ]);

    $newDepartment = Department::factory()->create();
    $newPosition = Position::factory()->create();

    $assignment = (new TransferEmployeeAction)->execute(
        employee: $employee,
        departmentId: $newDepartment->id,
        positionId: $newPosition->id,
        managerId: null,
        startedAt: Carbon::parse('2024-06-01'),
    );

    expect($assignment->isActive())->toBeTrue()
        ->and($assignment->department_id)->toBe($newDepartment->id)
        ->and($assignment->started_at->toDateString())->toBe('2024-06-01');

    expect($original->fresh()->ended_at->toDateString())->toBe('2024-05-31');
    expect($employee->assignments()->count())->toBe(2);
});

it('rejects a transfer dated before the current assignment started', function () {
    $employee = Employee::factory()->create();

    EmployeeAssignment::factory()->create([
        'employee_id' => $employee->id,
        'started_at' => '2024-06-01',
        'ended_at' => null,
    ]);

    $action = new TransferEmployeeAction;

    expect(fn () => $action->execute(
        employee: $employee,
        departmentId: Department::factory()->create()->id,
        positionId: Position::factory()->create()->id,
        managerId: null,
        startedAt: Carbon::parse('2024-01-01'),
    ))->toThrow(InvalidAssignmentPeriodException::class);
});

it('rejects assigning an employee as their own manager', function () {
    $employee = Employee::factory()->create();

    $action = new TransferEmployeeAction;

    expect(fn () => $action->execute(
        employee: $employee,
        departmentId: Department::factory()->create()->id,
        positionId: Position::factory()->create()->id,
        managerId: $employee->id,
        startedAt: Carbon::now(),
    ))->toThrow(InvalidAssignmentPeriodException::class);
});

it('rejects assigning a (indirect) subordinate as manager, since it would create a cycle', function () {
    $director = Employee::factory()->create();
    $subordinate = Employee::factory()->create();

    EmployeeAssignment::factory()->create([
        'employee_id' => $subordinate->id,
        'manager_id' => $director->id,
    ]);

    $action = new TransferEmployeeAction;

    expect(fn () => $action->execute(
        employee: $director,
        departmentId: Department::factory()->create()->id,
        positionId: Position::factory()->create()->id,
        managerId: $subordinate->id,
        startedAt: Carbon::now(),
    ))->toThrow(InvalidAssignmentPeriodException::class);
});
