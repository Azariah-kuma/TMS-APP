<?php

declare(strict_types=1);

use App\Actions\Employees\TransferEmployeeAction;
use App\Exceptions\EmployeeRetiredException;
use App\Exceptions\InvalidAssignmentPeriodException;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use Illuminate\Support\Carbon;

it('異動時に前の配属を終了し、新しい配属を開始する', function () {
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

it('現在の配属開始日より前の日付での異動は拒否される', function () {
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

it('従業員を自分自身の上司として設定することは拒否される', function () {
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

it('退職済みの従業員の異動は拒否される', function () {
    $employee = Employee::factory()->create(['retired_at' => now()->subDay()]);

    $action = new TransferEmployeeAction;

    expect(fn () => $action->execute(
        employee: $employee,
        departmentId: Department::factory()->create()->id,
        positionId: Position::factory()->create()->id,
        managerId: null,
        startedAt: Carbon::now(),
    ))->toThrow(EmployeeRetiredException::class);
});

it('退職済みの従業員を上司として設定することは拒否される', function () {
    $employee = Employee::factory()->create();
    $retiredManager = Employee::factory()->create(['retired_at' => now()->subDay()]);

    $action = new TransferEmployeeAction;

    expect(fn () => $action->execute(
        employee: $employee,
        departmentId: Department::factory()->create()->id,
        positionId: Position::factory()->create()->id,
        managerId: $retiredManager->id,
        startedAt: Carbon::now(),
    ))->toThrow(EmployeeRetiredException::class);
});

it('（間接的な）部下を上司として設定することは、循環関係になるため拒否される', function () {
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
