<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

/*
 * 役職マスタのポリシークラス。
 */
final class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    /** 役職マスタの新規作成は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function create(User $user): bool
    {
        return false;
    }

    /** 役職マスタの更新（名称・コードの訂正）は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function update(User $user, Position $position): bool
    {
        return false;
    }

    /** 役職マスタの削除（誤登録の取消）は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function delete(User $user, Position $position): bool
    {
        return false;
    }
}
