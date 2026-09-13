<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Actions\Employees\Concerns\GuardsAgainstRetiredEmployees;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class RetireEmployeeAction
{
    use GuardsAgainstRetiredEmployees;

    /** 退職日を設定し、現在の配属（あれば）を同じ日付で終了させる。 */
    public function execute(Employee $employee, Carbon $retiredAt): Employee
    {
        $this->assertNotRetired($employee, 'この従業員は既に退職済みです。');

        return DB::transaction(function () use ($employee, $retiredAt) {
            $employee->update(['retired_at' => $retiredAt]);

            $employee->currentAssignment()->update(['ended_at' => $retiredAt]);

            return $employee->fresh(['user', 'currentAssignment.department', 'currentAssignment.position']);
        });
    }
}
