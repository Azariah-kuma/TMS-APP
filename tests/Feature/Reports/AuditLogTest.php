<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use Laravel\Sanctum\Sanctum;

it('人事は監査ログを閲覧できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Sanctum::actingAs($hr->user);

    Department::factory()->create();

    $this->getJson('/api/audit-logs')
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
});

it('一般社員は監査ログを閲覧できない', function () {
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->getJson('/api/audit-logs')->assertForbidden();
});

it('auditable_type・auditable_idを指定して絞り込める', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    Sanctum::actingAs($hr->user);

    $department = Department::factory()->create();
    Department::factory()->create();

    $response = $this->getJson("/api/audit-logs?auditable_type=Department&auditable_id={$department->id}")
        ->assertOk();

    $ids = collect($response->json('data'))->pluck('auditable_id')->unique();

    expect($ids->all())->toBe([$department->id]);
});
