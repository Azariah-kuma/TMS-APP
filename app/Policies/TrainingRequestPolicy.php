<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\TrainingRequest;
use App\Models\User;

/*
 * 研修受講申請のポリシークラス。
 */
final class TrainingRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->employee !== null;
    }

    /** 本人・その上司（直接・間接を問わない）は閲覧可能（人事はGate::beforeで別途許可される）。 */
    public function view(User $user, TrainingRequest $trainingRequest): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->is($trainingRequest->employee) || $actor->isManagerOf($trainingRequest->employee);
    }

    /**
     * 研修受講の申請は、ログイン中の従業員なら誰でも自分の分を行える。
     */
    public function create(User $user, ?Employee $forEmployee = null): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        if ($forEmployee === null || $actor->is($forEmployee)) {
            return true;
        }

        return $actor->isManagerOf($forEmployee);
    }

    /**
     * 部署一括申請は、部下が1人以上いる（＝申請しうる対象が存在する）上司のみ
     * （人事はGate::beforeで別途許可される）。対象を実際に自部署・自分の部下に絞り込む処理は
     * BulkRequestTrainingForDepartmentAction側で行うため、ここでは「そもそも申請しうる立場か」だけを見る。
     */
    public function bulkCreate(User $user): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->subordinateIds()->isNotEmpty();
    }

    /**
     * 承認は申請者の上司のみ（人事はGate::beforeで別途許可される）。
     * ただし上司による代理申請（本人申請ではない）の場合は、申請元の上司自身も含めて
     * 上司側は誰も承認できない（既に受けさせる判断はした側のため、人事のみが承認する）。
     */
    public function approve(User $user, TrainingRequest $trainingRequest): bool
    {
        if (! $trainingRequest->isSelfRequested()) {
            return false;
        }

        return $this->isDecidableBy($user, $trainingRequest);
    }

    /** 却下も承認と同じ範囲（{@see self::approve()}参照）。 */
    public function reject(User $user, TrainingRequest $trainingRequest): bool
    {
        if (! $trainingRequest->isSelfRequested()) {
            return false;
        }

        return $this->isDecidableBy($user, $trainingRequest);
    }

    /** 取消は申請対象の本人、または（代理申請の場合は）申請した上司のみ（人事はGate::beforeで別途許可される）。 */
    public function cancel(User $user, TrainingRequest $trainingRequest): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->is($trainingRequest->employee) || $actor->is($trainingRequest->requestedBy);
    }

    /**
     * 多段階承認の場合、1段階目は申請対象本人の上司、2段階目以降は
     * 直前の段階を決裁した人の上司でなければならない（{@see TrainingRequest::currentStageApprovalTarget()}）。
     * 単層承認（required_approval_stages=1）では、従来通り申請対象本人の上司であればよい。
     */
    private function isDecidableBy(User $user, TrainingRequest $trainingRequest): bool
    {
        $actor = $user->employee;

        if ($actor === null) {
            return false;
        }

        return $actor->isManagerOf($trainingRequest->currentStageApprovalTarget());
    }
}
