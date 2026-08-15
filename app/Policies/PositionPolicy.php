<?php

declare(strict_types=1);

namespace App\Policies;

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

    /** 役職マスタの新規作成は人事のみ。 */
    public function create(User $user): bool
    {
        return $user->employee?->isHr() ?? false;
    }
}
