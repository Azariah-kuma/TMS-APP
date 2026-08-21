<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Position;
use Laravel\Sanctum\Sanctum;

it('人事はどの従業員でも閲覧できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $someone = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->getJson("/api/employees/{$someone->id}")
        ->assertOk()
        ->assertJsonPath('id', $someone->id);
});

it('従業員は自分自身のレコードを閲覧できる', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/employees/{$employee->id}")->assertOk();
});

it('上司は部下のレコードを閲覧できる', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);

    Sanctum::actingAs($manager->user);

    $this->getJson("/api/employees/{$subordinate->id}")->assertOk();
});

it('無関係な従業員のレコードは閲覧できない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/employees/{$other->id}")->assertForbidden();
});

it('一般社員は全従業員の一覧を閲覧できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/employees')->assertForbidden();
});

it('人事は全従業員の一覧を閲覧できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/employees')->assertOk();
});

it('一覧に各従業員の現在の部署名・役職名が含まれる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create(['name' => '開発部']);
    $position = Position::factory()->create(['name' => '課長']);
    createEmployeeWithAssignment(assignmentAttributes: [
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);

    Sanctum::actingAs($hr->user);

    $response = $this->getJson('/api/employees')->assertOk();

    $listed = collect($response->json())->firstWhere('employee_code', '!=', $hr->employee_code);

    expect($listed['current_assignment']['department_name'])->toBe('開発部')
        ->and($listed['current_assignment']['position_name'])->toBe('課長');
});

it('直属の部下を持つ従業員は、一覧・詳細どちらでも上司として扱われる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
    $individualContributor = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $list = collect($this->getJson('/api/employees')->assertOk()->json())->keyBy('id');

    expect($list[$manager->id]['is_manager'])->toBeTrue()
        ->and($list[$individualContributor->id]['is_manager'])->toBeFalse();

    $this->getJson("/api/employees/{$manager->id}")->assertOk()->assertJsonPath('is_manager', true);
    $this->getJson("/api/employees/{$individualContributor->id}")
        ->assertOk()
        ->assertJsonPath('is_manager', false);
});

it('直属の部下が異動すると、その上司は上司として扱われなくなる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $formerManager = createEmployeeWithAssignment();
    $report = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $formerManager->id]);

    Sanctum::actingAs($hr->user);

    // 部下を異動させ、上司との関係を終了させる。
    $this->postJson("/api/employees/{$report->id}/assignments", [
        'department_id' => $report->currentAssignment->department_id,
        'position_id' => $report->currentAssignment->position_id,
        'manager_id' => null,
        'started_at' => now()->addDay()->toDateString(),
    ])->assertCreated();

    $this->getJson("/api/employees/{$formerManager->id}")->assertOk()->assertJsonPath('is_manager', false);
});
