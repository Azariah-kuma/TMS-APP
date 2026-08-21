<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
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
