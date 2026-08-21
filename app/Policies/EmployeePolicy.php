<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

/*
 * 従業員マスタのポリシークラス。
 */
final class EmployeePolicy
{
    /** 従業員一覧の閲覧は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /** 本人・自分の部下（直接・間接を問わない）は閲覧可能（人事はGate::beforeで別途許可される）。 */
    public function view(User $user, Employee $employee): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->is($employee) || $actor->isManagerOf($employee);
    }

    /** 従業員の新規登録（オンボーディング）は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function create(User $user): bool
    {
        return false;
    }

    /** 部署・役職・上司の異動（履歴の追加）は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function transfer(User $user, Employee $employee): bool
    {
        return false;
    }

    /** 招待メールの再送信は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function resendInvite(User $user, Employee $employee): bool
    {
        return false;
    }
}
