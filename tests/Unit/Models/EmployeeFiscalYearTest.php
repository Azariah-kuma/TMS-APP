<?php

declare(strict_types=1);

use App\Models\Employee;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('今年度（4/1〜翌3/31）に入社した従業員はhiredInCurrentFiscalYear()がtrueになる', function () {
    Carbon::setTestNow('2026-08-15');

    $employee = Employee::factory()->create(['hired_at' => '2026-04-01']);

    expect($employee->hiredInCurrentFiscalYear())->toBeTrue();
});

it('前年度以前に入社した従業員はhiredInCurrentFiscalYear()がfalseになる', function () {
    Carbon::setTestNow('2026-08-15');

    $employee = Employee::factory()->create(['hired_at' => '2026-03-31']);

    expect($employee->hiredInCurrentFiscalYear())->toBeFalse();
});

it('年度をまたいだ1〜3月（前年4月始まりの年度扱い）でも今年度入社者を正しく判定できる', function () {
    // 「今日」が翌年の1〜3月の場合、年度は「前年4月1日〜今日の年の3月31日」になる。
    Carbon::setTestNow('2027-02-15');

    $employee = Employee::factory()->create(['hired_at' => '2026-04-01']);

    expect($employee->hiredInCurrentFiscalYear())->toBeTrue();
});

it('入社日が未設定の場合はhiredInCurrentFiscalYear()がfalseになる', function () {
    $employee = Employee::factory()->make(['hired_at' => null]);

    expect($employee->hiredInCurrentFiscalYear())->toBeFalse();
});
