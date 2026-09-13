<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\DepartmentBudget;
use Laravel\Sanctum\Sanctum;

it('人事は部署の年度予算を新規作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/department-budgets', [
        'department_id' => $department->id,
        'fiscal_year' => 2026,
        'budget_amount' => 500000,
    ])->assertCreated()->assertJsonPath('budget_amount', 500000);

    $this->assertDatabaseHas('department_budgets', [
        'department_id' => $department->id,
        'fiscal_year' => 2026,
    ]);
});

it('一般社員は部署の年度予算を新規作成できない', function () {
    $employee = createEmployeeWithAssignment();
    $department = Department::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/department-budgets', [
        'department_id' => $department->id,
        'fiscal_year' => 2026,
        'budget_amount' => 500000,
    ])->assertForbidden();
});

it('同じ部署・同じ年度の予算は重複登録できない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();
    DepartmentBudget::factory()->create(['department_id' => $department->id, 'fiscal_year' => 2026]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/department-budgets', [
        'department_id' => $department->id,
        'fiscal_year' => 2026,
        'budget_amount' => 500000,
    ])->assertStatus(422);
});

it('人事は部署の年度予算額を訂正できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $budget = DepartmentBudget::factory()->create(['budget_amount' => 100000]);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/department-budgets/{$budget->id}", [
        'budget_amount' => 200000,
    ])->assertOk()->assertJsonPath('budget_amount', 200000);
});

it('一般社員は部署の年度予算額を訂正できない', function () {
    $employee = createEmployeeWithAssignment();
    $budget = DepartmentBudget::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/department-budgets/{$budget->id}", [
        'budget_amount' => 200000,
    ])->assertForbidden();
});

it('人事は部署の年度予算一覧を閲覧できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    DepartmentBudget::factory()->count(2)->create();

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/department-budgets')->assertOk()->assertJsonCount(2);
});

it('一般社員は部署の年度予算一覧を閲覧できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/department-budgets')->assertForbidden();
});
