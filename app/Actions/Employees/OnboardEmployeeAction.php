<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class OnboardEmployeeAction
{
    public function __construct(
        private readonly TransferEmployeeAction $transferEmployeeAction,
    ) {}

    /**
     * ログイン用ユーザーと従業員レコードを作成し、初回の部署・役職・上司の割り当てを登録する。
     */
    public function execute(
        string $name,
        string $email,
        string $password,
        string $employeeCode,
        EmployeeRole $role,
        Carbon $hiredAt,
        int $departmentId,
        int $positionId,
        ?int $managerId,
    ): Employee {
        return DB::transaction(function () use (
            $name,
            $email,
            $password,
            $employeeCode,
            $role,
            $hiredAt,
            $departmentId,
            $positionId,
            $managerId,
        ) {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_code' => $employeeCode,
                'role' => $role,
                'hired_at' => $hiredAt,
            ]);

            $this->transferEmployeeAction->execute($employee, $departmentId, $positionId, $managerId, $hiredAt);

            return $employee->fresh(['user', 'currentAssignment']);
        });
    }
}
