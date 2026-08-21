<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Employee;
use Laravel\Sanctum\Sanctum;

it('人事は部署を新規作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/departments', [
        'name' => '人事部',
        'code' => 'DEPT-HR',
    ])->assertCreated()->assertJsonPath('name', '人事部');

    $this->assertDatabaseHas('departments', ['code' => 'DEPT-HR']);
});

it('一般社員は部署を新規作成できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/departments', [
        'name' => '人事部',
        'code' => 'DEPT-HR',
    ])->assertForbidden();
});

it('部署コードが重複していると作成を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Department::factory()->create(['code' => 'DEPT-HR']);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/departments', [
        'name' => '人事部',
        'code' => 'DEPT-HR',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('ログイン済みの従業員なら誰でも部署一覧を閲覧できる', function () {
    $employee = Employee::factory()->create();
    Department::factory()->count(2)->create();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/departments')->assertOk()->assertJsonCount(2);
});

it('未ログインのゲストは部署一覧を閲覧できない', function () {
    $this->getJson('/api/departments')->assertUnauthorized();
});
