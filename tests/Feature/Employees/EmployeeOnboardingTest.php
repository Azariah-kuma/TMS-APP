<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Notifications\SetInitialPasswordNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;

it('人事はパスワードを設定せずに、初期の部署・役職・上司つきで新規従業員を登録できる', function () {
    Notification::fake();

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $manager = createEmployeeWithAssignment();
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $response = $this->postJson('/api/employees', [
        'last_name' => '山田',
        'first_name' => '太郎',
        'last_name_kana' => 'ヤマダ',
        'first_name_kana' => 'タロウ',
        'email' => 'yamada@example.com',
        'employee_code' => 'EMP-0001',
        'role' => EmployeeRole::Employee->value,
        'hired_at' => '2026-04-01',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'manager_id' => $manager->id,
    ])->assertCreated();

    $employee = Employee::where('employee_code', 'EMP-0001')->firstOrFail();

    $response->assertJsonPath('id', $employee->id);

    // 登録直後のレスポンス自体にも部署・役職名が含まれている必要がある
    // （フォローアップのGETをしないと空欄になる、というリグレッションを防ぐ）。
    $response->assertJsonPath('current_assignment.department_name', $department->name)
        ->assertJsonPath('current_assignment.position_name', $position->name)
        ->assertJsonPath('is_manager', false);

    $this->assertDatabaseHas('users', ['email' => 'yamada@example.com']);
    expect($employee->currentAssignment->department_id)->toBe($department->id)
        ->and($employee->currentAssignment->position_id)->toBe($position->id)
        ->and($employee->currentAssignment->manager_id)->toBe($manager->id);

    Notification::assertSentTo($employee->user, SetInitialPasswordNotification::class);
});

it('一般社員は新規従業員を登録できない', function () {
    $employee = createEmployeeWithAssignment();
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Sanctum::actingAs($employee->user);

    $this->postJson('/api/employees', [
        'last_name' => '山田',
        'first_name' => '太郎',
        'last_name_kana' => 'ヤマダ',
        'first_name_kana' => 'タロウ',
        'email' => 'yamada@example.com',
        'employee_code' => 'EMP-0001',
        'role' => EmployeeRole::Employee->value,
        'hired_at' => '2026-04-01',
        'department_id' => $department->id,
        'position_id' => $position->id,
    ])->assertForbidden();
});

it('メールアドレスが重複している場合は登録を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $existing = createEmployeeWithAssignment();
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/employees', [
        'last_name' => '山田',
        'first_name' => '太郎',
        'last_name_kana' => 'ヤマダ',
        'first_name_kana' => 'タロウ',
        'email' => $existing->user->email,
        'employee_code' => 'EMP-0001',
        'role' => EmployeeRole::Employee->value,
        'hired_at' => '2026-04-01',
        'department_id' => $department->id,
        'position_id' => $position->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('department_id/position_idが文字列で送られてきても（HTMLの<select>と同じ形式）登録できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/employees', [
        'last_name' => '山田',
        'first_name' => '太郎',
        'last_name_kana' => 'ヤマダ',
        'first_name_kana' => 'タロウ',
        'email' => 'yamada@example.com',
        'employee_code' => 'EMP-0001',
        'role' => EmployeeRole::Employee->value,
        'hired_at' => '2026-04-01',
        'department_id' => (string) $department->id,
        'position_id' => (string) $position->id,
    ])->assertCreated();
});

it('存在しない部署を指定した場合は登録を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $position = Position::factory()->create();

    Sanctum::actingAs($hr->user);

    $this->postJson('/api/employees', [
        'last_name' => '山田',
        'first_name' => '太郎',
        'last_name_kana' => 'ヤマダ',
        'first_name_kana' => 'タロウ',
        'email' => 'yamada@example.com',
        'employee_code' => 'EMP-0001',
        'role' => EmployeeRole::Employee->value,
        'hired_at' => '2026-04-01',
        'department_id' => 999999,
        'position_id' => $position->id,
    ])->assertUnprocessable()->assertJsonValidationErrors('department_id');
});

it('人事は招待メールを再送信できる', function () {
    Notification::fake();

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/resend-invite")->assertOk();

    Notification::assertSentTo($employee->user, SetInitialPasswordNotification::class);
});

it('一般社員は招待メールを再送信できない', function () {
    $employee = createEmployeeWithAssignment();
    $someone = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->postJson("/api/employees/{$someone->id}/resend-invite")->assertForbidden();
});

it('招待メールの再送信に失敗した場合は、その旨を明確に返す', function () {
    Password::shouldReceive('sendResetLink')
        ->once()
        ->andThrow(new RuntimeException('SMTP connection refused'));

    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->postJson("/api/employees/{$employee->id}/resend-invite")->assertUnprocessable();
});
