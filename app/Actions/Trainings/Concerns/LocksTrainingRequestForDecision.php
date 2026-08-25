<?php

declare(strict_types=1);

namespace App\Actions\Trainings\Concerns;

use App\Enums\TrainingRequestStatus;
use App\Exceptions\TrainingRequestNotPendingException;
use App\Models\TrainingRequest;

/**
 * 承認・却下・取消は「承認待ちであること」を前提に状態を書き換える。
 * ロックせずにチェックすると、同じ申請への承認と取消がほぼ同時に実行された場合、
 * 両方が承認待ちチェックを通過してしまい、片方の結果がもう片方を上書きする形で
 * 矛盾した状態（例: 承認済みなのにキャンセル）になり得る。
 * 呼び出し元は必ず DB::transaction 内でこのメソッドを呼ぶこと！
 */
trait LocksTrainingRequestForDecision
{
    protected function lockPendingTrainingRequest(TrainingRequest $trainingRequest): TrainingRequest
    {
        $locked = TrainingRequest::query()->lockForUpdate()->findOrFail($trainingRequest->id);

        if ($locked->status !== TrainingRequestStatus::Pending) {
            throw new TrainingRequestNotPendingException('この申請は既に承認待ちではありません。');
        }

        return $locked;
    }
}
