<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/*
 * 監査ログのポリシークラス。
 */
final class AuditLogPolicy
{
    /** 監査ログの閲覧は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function viewAny(User $user): bool
    {
        return false;
    }
}
