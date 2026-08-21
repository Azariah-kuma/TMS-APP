<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class OnboardEmployeeAction
{
    public function __construct(
        private readonly TransferEmployeeAction $transferEmployeeAction,
        private readonly SendEmployeeInviteAction $sendEmployeeInviteAction,
    ) {}

    /**
     * ログイン用ユーザーと従業員レコードを作成し、初回の部署・役職・上司の割り当てを登録する。
     *
     * パスワードはHRには入力させず、誰にも推測できないランダム値で初期化した上で、
     * 本人がパスワードを設定できる招待メール（Laravel標準のパスワードリセット機構を流用）を送る。
     */
    public function execute(
        string $lastName,
        string $firstName,
        string $lastNameKana,
        string $firstNameKana,
        string $email,
        string $employeeCode,
        EmployeeRole $role,
        Carbon $hiredAt,
        int $departmentId,
        int $positionId,
        ?int $managerId,
    ): Employee {
        $employee = DB::transaction(function () use (
            $lastName,
            $firstName,
            $lastNameKana,
            $firstNameKana,
            $email,
            $employeeCode,
            $role,
            $hiredAt,
            $departmentId,
            $positionId,
            $managerId,
        ) {
            $user = User::create([
                'last_name' => $lastName,
                'first_name' => $firstName,
                'last_name_kana' => $lastNameKana,
                'first_name_kana' => $firstNameKana,
                'email' => $email,
                'password' => Hash::make(Str::random(40)),
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_code' => $employeeCode,
                'role' => $role,
                'hired_at' => $hiredAt,
            ]);

            $this->transferEmployeeAction->execute($employee, $departmentId, $positionId, $managerId, $hiredAt);

            // is_manager はここでは付与しない
            return $employee->fresh(['user', 'currentAssignment.department', 'currentAssignment.position']);
        });

        // トランザクションのコミット後に送る（ロールバック時に招待メールだけ届くのを防ぐ）。
        // 失敗はログに残り、HRは招待メール再送APIで再送できる
        $this->sendEmployeeInviteAction->execute($email);

        return $employee;
    }
}
