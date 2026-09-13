<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * 会計年度（4/1〜翌3/31）に関するユーティリティ。年度は開始年（4月）の西暦で表す
 * （例: 2026年4月〜2027年3月 は fiscal_year=2026）。
 */
final class FiscalYear
{
    /** 今日時点の会計年度。 */
    public static function current(): int
    {
        return self::of(Carbon::today());
    }

    /** $date が属する会計年度。 */
    public static function of(Carbon $date): int
    {
        return $date->month >= 4 ? $date->year : $date->year - 1;
    }

    /** $fiscalYear の開始日（4/1）。 */
    public static function startOf(int $fiscalYear): Carbon
    {
        return Carbon::create($fiscalYear, 4, 1)->startOfDay();
    }

    /** $fiscalYear の終了日（翌3/31）。 */
    public static function endOf(int $fiscalYear): Carbon
    {
        return self::startOf($fiscalYear)->addYear()->subDay()->endOfDay();
    }
}
