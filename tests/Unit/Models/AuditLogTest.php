<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Department;
use Laravel\Sanctum\Sanctum;

it('操作した従業員を取得できる', function () {
    $actor = createEmployeeWithAssignment();
    Sanctum::actingAs($actor->user);

    $department = Department::factory()->create();

    $log = AuditLog::query()->where('auditable_id', $department->id)->sole();

    expect($log->actor->is($actor))->toBeTrue();
});

it('操作した従業員が不明な場合はnullを返す', function () {
    $department = Department::factory()->create();

    $log = AuditLog::query()->where('auditable_id', $department->id)->sole();

    expect($log->actor)->toBeNull();
});
