<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Position;
use Laravel\Sanctum\Sanctum;

it('人事は従業員を新しい部署・役職に異動させられる（履歴は残る）', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment(assignmentAttributes: ['started_at' => '2024-01-01']);

    $newDepartment = Department::factory()->create();
    $newPosition = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/assignments", [
        'department_id' => $newDepartment->id,
        'position_id' => $newPosition->id,
        'manager_id' => null,
        'started_at' => '2024-06-01',
    ])->assertCreated();

    expect($employee->assignments()->count())->toBe(2)
        ->and($employee->currentAssignment()->first()->department_id)->toBe($newDepartment->id);
});

it('department_id/position_idが文字列で送られてきても（HTMLの<select>と同じ形式）異動できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    $newDepartment = Department::factory()->create();
    $newPosition = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/assignments", [
        'department_id' => (string) $newDepartment->id,
        'position_id' => (string) $newPosition->id,
        'started_at' => now()->toDateString(),
    ])->assertCreated();
});

it('一般社員は自分自身を異動させられない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/employees/{$employee->id}/assignments", [
        'department_id' => Department::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'started_at' => now()->toDateString(),
    ])->assertForbidden();
});

it('上司は自分の部下を異動させられない（人事権限が必要）', function () {
    $manager = createEmployeeWithAssignment();
    $subordinate = createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);

    Sanctum::actingAs($manager->user);

    $this->postJson("/api/employees/{$subordinate->id}/assignments", [
        'department_id' => Department::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'started_at' => now()->toDateString(),
    ])->assertForbidden();
});

it('従業員は自分の異動履歴を新しい順に取得できる', function () {
    $employee = createEmployeeWithAssignment(assignmentAttributes: ['started_at' => '2024-01-01']);

    Sanctum::actingAs(createEmployeeWithAssignment(['role' => EmployeeRole::Hr])->user);
    $this->postJson("/api/employees/{$employee->id}/assignments", [
        'department_id' => Department::factory()->create()->id,
        'position_id' => Position::factory()->create()->id,
        'started_at' => '2024-06-01',
    ])->assertCreated();

    Sanctum::actingAs($employee->user);

    $response = $this->getJson("/api/employees/{$employee->id}/assignments")->assertOk();

    expect($response->json())->toHaveCount(2)
        ->and($response->json('0.started_at'))->toBe('2024-06-01');
});

it('無関係な従業員は他人の異動履歴を閲覧できない', function () {
    $employee = createEmployeeWithAssignment();
    $bystander = createEmployeeWithAssignment();

    Sanctum::actingAs($bystander->user);

    $this->getJson("/api/employees/{$employee->id}/assignments")->assertForbidden();
});
