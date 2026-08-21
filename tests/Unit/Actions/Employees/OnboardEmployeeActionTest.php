<?php

declare(strict_types=1);

use App\Actions\Employees\OnboardEmployeeAction;
use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Notifications\SetInitialPasswordNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('新入社員のユーザー・従業員・初回配属を作成し、初期パスワードはログイン不能な状態にする', function () {
    Notification::fake();

    $department = Department::factory()->create();
    $position = Position::factory()->create();
    $manager = Employee::factory()->create();

    $employee = app(OnboardEmployeeAction::class)->execute(
        lastName: '山田',
        firstName: '太郎',
        lastNameKana: 'ヤマダ',
        firstNameKana: 'タロウ',
        email: 'taro.yamada@example.com',
        employeeCode: 'EMP-0001',
        role: EmployeeRole::Employee,
        hiredAt: Carbon::parse('2026-04-01'),
        departmentId: $department->id,
        positionId: $position->id,
        managerId: $manager->id,
    );

    expect($employee->employee_code)->toBe('EMP-0001')
        ->and($employee->role)->toBe(EmployeeRole::Employee)
        ->and($employee->user->name)->toBe('山田太郎')
        ->and($employee->user->nameKana)->toBe('ヤマダタロウ')
        ->and($employee->user->email)->toBe('taro.yamada@example.com')
        // HRはパスワードを一切指定できないため、既知の値ではログインできないことを確認する。
        ->and(Hash::check('password', $employee->user->password))->toBeFalse();

    $assignment = $employee->currentAssignment;
    expect($assignment)->not->toBeNull()
        ->and($assignment->department_id)->toBe($department->id)
        ->and($assignment->position_id)->toBe($position->id)
        ->and($assignment->manager_id)->toBe($manager->id)
        ->and($assignment->started_at->toDateString())->toBe('2026-04-01');

    Notification::assertSentTo($employee->user, SetInitialPasswordNotification::class);
});

it('上司なしで新入社員を作成できる', function () {
    Notification::fake();

    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $employee = app(OnboardEmployeeAction::class)->execute(
        lastName: '鈴木',
        firstName: '花子',
        lastNameKana: 'スズキ',
        firstNameKana: 'ハナコ',
        email: 'hanako.suzuki@example.com',
        employeeCode: 'EMP-0002',
        role: EmployeeRole::Hr,
        hiredAt: Carbon::parse('2026-04-01'),
        departmentId: $department->id,
        positionId: $position->id,
        managerId: null,
    );

    expect($employee->currentAssignment->manager_id)->toBeNull();
});

it('招待メールの送信に失敗しても、ユーザー・従業員・配属は作成される', function () {
    Password::shouldReceive('sendResetLink')->once()->andThrow(new RuntimeException('SMTP connection refused'));
    Log::shouldReceive('error')->once();

    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $employee = app(OnboardEmployeeAction::class)->execute(
        lastName: '障害',
        firstName: '太郎',
        lastNameKana: 'ショウガイ',
        firstNameKana: 'タロウ',
        email: 'shogai.taro@example.com',
        employeeCode: 'EMP-0004',
        role: EmployeeRole::Employee,
        hiredAt: Carbon::parse('2026-04-01'),
        departmentId: $department->id,
        positionId: $position->id,
        managerId: null,
    );

    expect($employee->employee_code)->toBe('EMP-0004')
        ->and($employee->currentAssignment)->not->toBeNull();
});

it('既に使用されているメールアドレスでの入社登録は拒否され、中途半端なレコードも残らない', function () {
    Notification::fake();

    $existing = Employee::factory()->create();
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $userCountBefore = User::query()->count();
    $employeeCountBefore = Employee::query()->count();

    expect(fn () => app(OnboardEmployeeAction::class)->execute(
        lastName: '重複',
        firstName: '太郎',
        lastNameKana: 'チョウフク',
        firstNameKana: 'タロウ',
        email: $existing->user->email,
        employeeCode: 'EMP-0003',
        role: EmployeeRole::Employee,
        hiredAt: Carbon::now(),
        departmentId: $department->id,
        positionId: $position->id,
        managerId: null,
    ))->toThrow(QueryException::class);

    expect(User::query()->count())->toBe($userCountBefore)
        ->and(Employee::query()->count())->toBe($employeeCountBefore);

    Notification::assertNothingSent();
});
