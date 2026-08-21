<?php

declare(strict_types=1);

namespace App\Actions\Employees\Concerns;

use App\Exceptions\EmployeeRetiredException;
use App\Models\Employee;

/**
 * 退職済み（retired_at設定済み）の従業員を、研修登録・委任・異動などの対象にできないよう一律にガードする。
 * 同じ判定がAction間でバラバラに実装されていると、
 * 将来retired_atの意味付けを変える際に見落としが起きやすいため、ここに一元化する。
 */
trait GuardsAgainstRetiredEmployees
{
    protected function assertNotRetired(?Employee $employee, string $message): void
    {
        if ($employee?->retired_at !== null) {
            throw new EmployeeRetiredException($message);
        }
    }
}
