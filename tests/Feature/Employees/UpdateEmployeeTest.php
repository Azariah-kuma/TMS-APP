<?php

declare(strict_types=1);

use App\Enums\EmployeeRole;
use Laravel\Sanctum\Sanctum;

it('人事は婚姻等による姓の変更として従業員の氏名・フリガナを訂正できる', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/employees/{$employee->id}", [
        'last_name' => '鈴木',
        'first_name' => $employee->user->first_name,
        'last_name_kana' => 'スズキ',
        'first_name_kana' => $employee->user->first_name_kana,
    ])->assertOk()->assertJsonPath('last_name', '鈴木');

    expect($employee->user->fresh()->last_name)->toBe('鈴木');
});

it('一般社員は従業員の氏名を訂正できない', function () {
    $employee = createEmployeeWithAssignment();
    $other = createEmployeeWithAssignment();

    Sanctum::actingAs($employee->user);

    $this->patchJson("/api/employees/{$other->id}", [
        'last_name' => '鈴木',
        'first_name' => $other->user->first_name,
        'last_name_kana' => 'スズキ',
        'first_name_kana' => $other->user->first_name_kana,
    ])->assertForbidden();
});

it('フリガナがカタカナでない場合は訂正を拒否する', function () {
    $hr = createEmployeeWithAssignment(['role' => EmployeeRole::Hr]);
    $employee = createEmployeeWithAssignment();

    Sanctum::actingAs($hr->user);

    $this->patchJson("/api/employees/{$employee->id}", [
        'last_name' => '鈴木',
        'first_name' => $employee->user->first_name,
        'last_name_kana' => 'すずき',
        'first_name_kana' => $employee->user->first_name_kana,
    ])->assertStatus(422);
});
