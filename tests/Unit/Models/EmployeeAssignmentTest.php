<?php

declare(strict_types=1);

use App\Models\Employee;
use App\Models\EmployeeAssignment;

it('配属先の従業員に属する', function () {
    $employee = Employee::factory()->create();
    $assignment = EmployeeAssignment::factory()->create(['employee_id' => $employee->id]);

    expect($assignment->employee->is($employee))->toBeTrue();
});

it('配属に設定された上司に属する', function () {
    $manager = Employee::factory()->create();
    $assignment = EmployeeAssignment::factory()->create(['manager_id' => $manager->id]);

    expect($assignment->manager->is($manager))->toBeTrue();
});

it('上司が設定されていない場合はnullになる', function () {
    $assignment = EmployeeAssignment::factory()->create(['manager_id' => null]);

    expect($assignment->manager)->toBeNull();
});

it('ended_atがnullの間は有効', function () {
    $assignment = EmployeeAssignment::factory()->create(['ended_at' => null]);

    expect($assignment->isActive())->toBeTrue();
});

it('ended_atが設定されると無効になる', function () {
    $assignment = EmployeeAssignment::factory()->ended()->create();

    expect($assignment->isActive())->toBeFalse();
});
