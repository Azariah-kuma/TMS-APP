<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Employee;
use Laravel\Sanctum\Sanctum;

it('人事は従業員を退職させ、現在の配属を同日付で終了させる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/retire", [
        'retired_at' => '2026-12-31',
    ])->assertOk()->assertJsonPath('retired_at', '2026-12-31');

    $fresh = $employee->fresh();
    expect($fresh->retired_at->toDateString())->toBe('2026-12-31')
        ->and($fresh->currentAssignment)->toBeNull();

    $endedAssignment = $employee->assignments()->first();
    expect($endedAssignment->ended_at->toDateString())->toBe('2026-12-31');
});

it('配属の無い従業員（外部監査等）も退職登録できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = Employee::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/retire", [
        'retired_at' => '2026-12-31',
    ])->assertOk();

    expect($employee->fresh()->retired_at)->not->toBeNull();
});

it('一般社員は従業員を退職させられない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/employees/{$other->id}/retire", [
        'retired_at' => '2026-12-31',
    ])->assertForbidden();
});

it('既に退職済みの従業員を再度退職させることはできない', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment(['retired_at' => '2026-01-01']);

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/retire", [
        'retired_at' => '2026-12-31',
    ])->assertStatus(422);
});

it('退職日が未指定の場合はバリデーションエラーになる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/retire", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors('retired_at');
});
