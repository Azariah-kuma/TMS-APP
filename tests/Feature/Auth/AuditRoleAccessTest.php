<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use Laravel\Sanctum\Sanctum;

it('監査ロールは閲覧系のエンドポイントにアクセスできる', function () {
    $auditor = createEmployeeWithAssignment(['role' => EmployeeRole::Audit]);
    createEmployeeWithAssignment();

    Sanctum::actingAs($auditor->user);

    $this->getJson('/api/employees')->assertOk();
    $this->getJson('/api/reports/training-summary')->assertOk();
});

it('監査ロールは作成・更新・削除等の操作はできない', function () {
    $auditor = createEmployeeWithAssignment(['role' => EmployeeRole::Audit]);
    $department = Department::factory()->create();

    Sanctum::actingAs($auditor->user);

    $this->postJson('/api/departments', ['name' => '新設部署', 'code' => 'NEW-DEPT'])->assertForbidden();
    $this->patchJson("/api/departments/{$department->id}", ['name' => '変更後', 'code' => $department->code])
        ->assertForbidden();
    $this->deleteJson("/api/departments/{$department->id}")->assertForbidden();
});
