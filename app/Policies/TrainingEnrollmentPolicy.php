<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\TrainingEnrollment;
use App\Models\User;

/*
 * 研修受講記録のポリシークラス。
 */
final class TrainingEnrollmentPolicy
{
    /**
     * 一覧の要求自体はログイン中の従業員なら誰でも可能。
     *
     * 実際にどの受講記録が見えるかは TrainingEnrollment::visibleTo() スコープでロールに応じて絞り込む（一般社員: 自分のみ／上司: 自分と部下／人事: 全件）。
     */
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    /** 本人・その上司（直接・間接を問わない）・人事は閲覧可能。 */
    public function view(User $user, TrainingEnrollment $enrollment): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->isHr()
            || $actor->is($enrollment->employee)
            || $actor->isManagerOf($enrollment->employee);
    }

    /** 研修の割り当て（受講登録）は人事のみ。 */
    public function create(User $user): bool
    {
        return $user->employee?->isHr() ?? false;
    }

    /**
     * 進捗の更新は本人か人事のみ。
     *
     * 上司は「部下の進捗参照」までが権限であり、代理で更新することはできない。
     */
    public function update(User $user, TrainingEnrollment $enrollment): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->isHr() || $actor->is($enrollment->employee);
    }

    /** 受講登録の取り消しは人事のみ。 */
    public function delete(User $user, TrainingEnrollment $enrollment): bool
    {
        return $user->employee?->isHr() ?? false;
    }
}
