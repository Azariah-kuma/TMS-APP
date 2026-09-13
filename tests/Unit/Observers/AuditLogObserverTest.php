<?php

declare(strict_types=1);

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\Department;
use Laravel\Sanctum\Sanctum;

it('モデルの作成時に、属性一式を記録した監査ログを作成する', function () {
    $department = Department::factory()->create(['name' => '営業部', 'code' => 'DEPT-SALES']);

    $log = AuditLog::query()->where('auditable_id', $department->id)->where('auditable_type', Department::class)->sole();

    expect($log->action)->toBe(AuditLogAction::Created)
        ->and($log->changes['name'])->toBe('営業部')
        ->and($log->changes)->not->toHaveKey('created_at')
        ->and($log->actor_employee_id)->toBeNull();
});

it('ログイン中の従業員が操作した場合、その従業員が記録される', function () {
    $actor = createEmployeeWithAssignment();
    Sanctum::actingAs($actor->user);

    $department = Department::factory()->create();

    $log = AuditLog::query()->where('auditable_id', $department->id)->where('action', AuditLogAction::Created)->sole();

    expect($log->actor_employee_id)->toBe($actor->id);
});

it('モデルの更新時に、変更のあった属性のみを記録する', function () {
    $department = Department::factory()->create(['name' => '営業部']);

    $department->update(['name' => '営業推進部']);

    $log = AuditLog::query()->where('auditable_id', $department->id)->where('action', AuditLogAction::Updated)->sole();

    expect($log->changes)->toBe(['name' => '営業推進部']);
});

it('updated_atのみが変化した更新は監査ログに記録しない', function () {
    $department = Department::factory()->create();

    // touch()は直前のcreate()と同一秒に実行されると差分なしとみなされ保存自体が
    // スキップされてしまうため、確実に差分が生まれるよう1分先の値を明示的に設定する。
    $department->forceFill(['updated_at' => now()->addMinute()])->save();

    $count = AuditLog::query()->where('auditable_id', $department->id)->where('action', AuditLogAction::Updated)->count();

    expect($count)->toBe(0);
});

it('モデルの削除時に、削除時点の属性一式を記録する', function () {
    $department = Department::factory()->create(['name' => '営業部']);
    $departmentId = $department->id;

    $department->delete();

    $log = AuditLog::query()->where('auditable_id', $departmentId)->where('action', AuditLogAction::Deleted)->sole();

    expect($log->changes['name'])->toBe('営業部');
});
