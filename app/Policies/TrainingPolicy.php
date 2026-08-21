<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Training;
use App\Models\User;

/**
 * 研修カタログ（Training）そのものの管理権限。
 * 受講状況は TrainingEnrollmentPolicy を参照。
 */
final class TrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    public function view(User $user, Training $training): bool
    {
        return $user->employee !== null;
    }

    /** 研修の新規作成は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function create(User $user): bool
    {
        return false;
    }

    /** 研修情報の更新は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function update(User $user, Training $training): bool
    {
        return false;
    }

    /** 研修の削除は人事のみ（HRの許可自体はGate::beforeで一元的に処理される）。 */
    public function delete(User $user, Training $training): bool
    {
        return false;
    }
}
