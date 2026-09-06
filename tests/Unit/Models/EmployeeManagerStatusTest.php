<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\EmployeeAssignment;

it('この従業員が現在上司として設定されている配属を取得できる', function () {
    $manager = Employee::factory()->create();
    $report = Employee::factory()->create();

    $assignment = EmployeeAssignment::factory()->create([
        'employee_id' => $report->id,
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);

    expect($manager->currentDirectReportAssignments()->pluck('id'))->toEqual(collect([$assignment->id]));
});

it('終了済みの配属は現在の直属部下から除外される', function () {
    $manager = Employee::factory()->create();

    EmployeeAssignment::factory()->create([
        'employee_id' => Employee::factory()->create()->id,
        'manager_id' => $manager->id,
        'started_at' => '2023-01-01',
        'ended_at' => '2023-06-01',
    ]);

    expect($manager->currentDirectReportAssignments()->count())->toBe(0);
});

it('現在有効な直属部下を持つ従業員はisManager()がtrueになる', function () {
    $manager = Employee::factory()->create();

    EmployeeAssignment::factory()->create([
        'employee_id' => Employee::factory()->create()->id,
        'manager_id' => $manager->id,
        'ended_at' => null,
    ]);

    expect($manager->isManager())->toBeTrue();
});

it('直属部下を持たない従業員はisManager()がfalseになる', function () {
    $employee = Employee::factory()->create();

    expect($employee->isManager())->toBeFalse();
});

it('部下の配属が終了済みの場合はisManager()がfalseになる', function () {
    $manager = Employee::factory()->create();

    EmployeeAssignment::factory()->create([
        'employee_id' => Employee::factory()->create()->id,
        'manager_id' => $manager->id,
        'started_at' => '2023-01-01',
        'ended_at' => '2023-06-01',
    ]);

    expect($manager->isManager())->toBeFalse();
});
