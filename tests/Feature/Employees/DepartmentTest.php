<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Training;
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

it('人事は統合・組織変更に伴い部署名・部署コードを訂正できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create(['name' => '開発部　', 'code' => 'DEPT-TYPO']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/departments/{$department->id}", [
        'name' => '開発部',
        'code' => 'DEPT-DEV',
    ])->assertOk()->assertJsonPath('name', '開発部');

    $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => '開発部', 'code' => 'DEPT-DEV']);
});

it('更新時、他の部署と同じコードには変更できない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Department::factory()->create(['code' => 'DEPT-A']);
    $target = Department::factory()->create(['code' => 'DEPT-B']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/departments/{$target->id}", [
        'name' => $target->name,
        'code' => 'DEPT-A',
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('更新時、自分自身と同じコードを指定してもエラーにならない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create(['code' => 'DEPT-A']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/departments/{$department->id}", [
        'name' => '新しい名前',
        'code' => 'DEPT-A',
    ])->assertOk();
});

it('一般社員は部署を訂正できない', function () {
    $employee = createEmployeeWithAssignment();
    $department = Department::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/departments/{$department->id}", [
        'name' => '新しい名前',
        'code' => $department->code,
    ])->assertForbidden();
});

it('人事は使われていない部署を削除できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/departments/{$department->id}")->assertNoContent();

    expect(Department::find($department->id))->toBeNull();
});

it('配属履歴で使用されている部署は削除できない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();
    EmployeeAssignment::factory()->create(['department_id' => $department->id]);

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/departments/{$department->id}")->assertStatus(422);

    expect(Department::find($department->id))->not->toBeNull();
});

it('研修の閲覧対象部署として使用されている部署は削除できない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();
    Training::factory()->create(['audience_department_id' => $department->id]);

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/departments/{$department->id}")->assertStatus(422);

    expect(Department::find($department->id))->not->toBeNull();
});

it('一般社員は部署を削除できない', function () {
    $employee = createEmployeeWithAssignment();
    $department = Department::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->deleteJson("/api/departments/{$department->id}")->assertForbidden();

    expect(Department::find($department->id))->not->toBeNull();
});
