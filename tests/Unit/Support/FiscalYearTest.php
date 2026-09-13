<?php

declare(strict_types=1);

use App\Support\FiscalYear;
use Illuminate\Support\Carbon;

afterEach(function () {
    Carbon::setTestNow();
});

it('4月以降は当年を会計年度とする', function () {
    Carbon::setTestNow(Carbon::create(2026, 4, 1));

    expect(FiscalYear::current())->toBe(2026);
});

it('1〜3月は前年を会計年度とする', function () {
    Carbon::setTestNow(Carbon::create(2027, 3, 31));

    expect(FiscalYear::current())->toBe(2026);
});

it('会計年度の開始日は4/1、終了日は翌3/31', function () {
    expect(FiscalYear::startOf(2026)->toDateString())->toBe('2026-04-01')
        ->and(FiscalYear::endOf(2026)->toDateString())->toBe('2027-03-31');
});

it('日付から会計年度を判定できる', function () {
    expect(FiscalYear::of(Carbon::create(2026, 4, 1)))->toBe(2026)
        ->and(FiscalYear::of(Carbon::create(2027, 3, 31)))->toBe(2026)
        ->and(FiscalYear::of(Carbon::create(2026, 3, 31)))->toBe(2025);
});
