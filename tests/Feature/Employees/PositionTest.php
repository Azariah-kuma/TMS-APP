<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\Position;
use Laravel\Sanctum\Sanctum;

it('人事は役職を新規作成できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
        'rank' => 3,
    ])->assertCreated()->assertJsonPath('name', '課長');

    $this->assertDatabaseHas('positions', ['code' => 'POS-MGR', 'rank' => 3]);
});

it('一般社員は役職を新規作成できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
        'rank' => 3,
    ])->assertForbidden();
});

it('役職コードが重複していると作成を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Position::factory()->create(['code' => 'POS-MGR']);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
        'rank' => 3,
    ])->assertUnprocessable()->assertJsonValidationErrors('code');
});

it('rankが整数でない場合は作成を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/positions', [
        'name' => '課長',
        'code' => 'POS-MGR',
        'rank' => 'high',
    ])->assertUnprocessable()->assertJsonValidationErrors('rank');
});

it('ログイン済みの従業員なら誰でも、役職一覧をrank順で閲覧できる', function () {
    $employee = Employee::factory()->create();
    Position::factory()->create(['rank' => 5]);
    Position::factory()->create(['rank' => 1]);

    Sanctum::actingAs($employee->user);

    $response = $this->getJson('/api/positions')->assertOk()->assertJsonCount(2);

    expect($response->json('0.rank'))->toBe(1)
        ->and($response->json('1.rank'))->toBe(5);
});

it('未ログインのゲストは役職一覧を閲覧できない', function () {
    $this->getJson('/api/positions')->assertUnauthorized();
});
