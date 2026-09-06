<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

/*
 * 部署マスタのポリシークラス。
 */
final class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    /** 部署マスタの新規作成は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function create(User $user): bool
    {
        return false;
    }

    /** 部署マスタの更新は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function update(User $user, Department $department): bool
    {
        return false;
    }

    /** 部署マスタの削除は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function delete(User $user, Department $department): bool
    {
        return false;
    }
}
