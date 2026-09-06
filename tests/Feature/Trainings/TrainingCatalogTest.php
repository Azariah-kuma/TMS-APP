<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Training;
use Laravel\Sanctum\Sanctum;

it('人事は研修を新規作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/trainings', [
        'title' => '情報セキュリティ研修',
        'category' => '情報セキュリティ',
    ])->assertCreated()->assertJsonPath('title', '情報セキュリティ研修');
});

it('一般社員は研修を新規作成できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/trainings', ['title' => '情報セキュリティ研修'])->assertForbidden();
});

it('ログイン済みの従業員なら誰でも研修カタログを閲覧できる', function () {
    $employee = createEmployeeWithAssignment();
    Training::factory()->count(2)->create();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(2);
});

it('ログイン済みの従業員なら誰でも研修の詳細を閲覧できる', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->getJson("/api/trainings/{$training->id}")->assertOk()->assertJsonPath('id', $training->id);
});

it('人事は研修情報を更新できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create(['title' => '旧タイトル']);

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/trainings/{$training->id}", ['title' => '新タイトル'])
        ->assertOk()
        ->assertJsonPath('title', '新タイトル');
});

it('一般社員は研修情報を更新できない', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/trainings/{$training->id}", ['title' => '新タイトル'])->assertForbidden();
});

it('タイトルを空にする更新は拒否される', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/trainings/{$training->id}", ['title' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

it('人事は研修を削除できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $training = Training::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->deleteJson("/api/trainings/{$training->id}")->assertNoContent();

    expect(Training::find($training->id))->toBeNull();
});

it('一般社員は研修を削除できない', function () {
    $employee = createEmployeeWithAssignment();
    $training = Training::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->deleteJson("/api/trainings/{$training->id}")->assertForbidden();

    expect(Training::find($training->id))->not->toBeNull();
});

it('人事は対象者（対象部署・管理職・新入社員）を指定して研修を作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();

    Sanctum::actingAs($hr->user);

    $response = $this->postJson('/api/trainings', [
        'title' => '開発部向け研修',
        'audience_department_id' => $department->id,
        'audience_managers_only' => true,
    ])->assertCreated();

    $response->assertJsonPath('audience_department_id', $department->id)
        ->assertJsonPath('audience_managers_only', true)
        ->assertJsonPath('audience_new_hires_only', false);
});

it('人事は多段階承認が必要な研修として、承認段階数を指定して作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $response = $this->postJson('/api/trainings', [
        'title' => '高額な海外研修',
        'requires_multistage_approval' => true,
        'approval_stage_count' => 3,
    ])->assertCreated();

    $response->assertJsonPath('requires_multistage_approval', true)
        ->assertJsonPath('approval_stage_count', 3);
});

it('多段階承認が必要な研修は、承認段階数（2以上）を指定しないと作成を拒否される', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/trainings', [
        'title' => '高額な海外研修',
        'requires_multistage_approval' => true,
    ])->assertUnprocessable()->assertJsonValidationErrors('approval_stage_count');
});

it('多段階承認が必要な研修は、対象部署でなくても管理職の一覧には表示される', function () {
    Training::factory()->create([
        'requires_multistage_approval' => true,
        'approval_stage_count' => 2,
        'title' => '高額な海外研修',
    ]);

    $manager = createEmployeeWithAssignment();
    createEmployeeWithAssignment([], ['manager_id' => $manager->id]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($manager->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(1);

    Sanctum::actingAs($employee->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(0);
});

it('対象部署を指定した研修は、その部署に所属する従業員の一覧にのみ表示される', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    Training::factory()->create(['audience_department_id' => $department->id, 'title' => '開発部向け研修']);
    Training::factory()->create(['title' => '全員向け研修']);

    $memberEmployee = createEmployeeWithAssignment([], ['department_id' => $department->id]);
    $otherEmployee = createEmployeeWithAssignment([], ['department_id' => $otherDepartment->id]);

    Sanctum::actingAs($memberEmployee->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(2);

    Sanctum::actingAs($otherEmployee->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(1)
        ->assertJsonPath('0.title', '全員向け研修');
});

it('対象部署を指定した研修は、対象外の部署の従業員が詳細を見ようとすると403になる', function () {
    $department = Department::factory()->create();
    $otherDepartment = Department::factory()->create();
    $training = Training::factory()->create(['audience_department_id' => $department->id]);

    $otherEmployee = createEmployeeWithAssignment([], ['department_id' => $otherDepartment->id]);

    Sanctum::actingAs($otherEmployee->user);

    $this->getJson("/api/trainings/{$training->id}")->assertForbidden();
});

it('管理職向けの研修は管理職の一覧にのみ表示される', function () {
    Training::factory()->create(['audience_managers_only' => true, 'title' => '管理職向け研修']);

    $manager = createEmployeeWithAssignment();
    createEmployeeWithAssignment([], ['manager_id' => $manager->id]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($manager->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(1);

    Sanctum::actingAs($employee->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(0);
});

it('新入社員向けの研修は今年度入社の従業員の一覧にのみ表示される', function () {
    Training::factory()->create(['audience_new_hires_only' => true, 'title' => '新入社員向け研修']);

    $newHire = createEmployeeWithAssignment(['hired_at' => now()]);
    $veteran = createEmployeeWithAssignment(['hired_at' => now()->subYears(5)]);

    Sanctum::actingAs($newHire->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(1);

    Sanctum::actingAs($veteran->user);
    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(0);
});

it('人事は対象者の制限に関わらず全ての研修を一覧で閲覧できる', function () {
    $department = Department::factory()->create();
    Training::factory()->create(['audience_department_id' => $department->id]);
    Training::factory()->create(['audience_managers_only' => true]);
    Training::factory()->create();

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->getJson('/api/trainings')->assertOk()->assertJsonCount(3);
});
