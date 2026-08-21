<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Delegation;
use App\Models\User;

/*
 * 委任のポリシークラス。
 */
final class DelegationPolicy
{
    /** 委任元・委任先本人は閲覧可能（人事はGate::beforeで別途許可される）。 */
    public function view(User $user, Delegation $delegation): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->is($delegation->delegator) || $actor->is($delegation->delegate);
    }

    /**
     * 委任の作成は人事のみ（組織権限の変更を一元管理するため。
     * HRの許可自体はGate::beforeで一元的に処理される）。
     */
    public function create(User $user): bool
    {
        return false;
    }

    /** 委任の取り消しは人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function delete(User $user, Delegation $delegation): bool
    {
        return false;
    }
}
