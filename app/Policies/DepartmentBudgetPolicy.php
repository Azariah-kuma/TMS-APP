<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DepartmentBudget;
use App\Models\User;

/*
 * 部署予算のポリシークラス。
 */
final class DepartmentBudgetPolicy
{
    /** 部署予算の閲覧は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /** 部署予算の新規作成は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function create(User $user): bool
    {
        return false;
    }

    /** 部署予算の更新は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function update(User $user, DepartmentBudget $departmentBudget): bool
    {
        return false;
    }
}
