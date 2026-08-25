<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Exceptions\TrainingRequestNotPendingException;
use App\Models\Employee;
use App\Models\TrainingRequest;
use Illuminate\Support\Collection;

final class BulkApproveTrainingRequestsAction
{
    public function __construct(
        private readonly ApproveTrainingRequestAction $approveTrainingRequestAction,
    ) {}

    /**
     * 複数の申請をまとめて承認する。承認権限がある申請だけが渡ってくる前提（絞り込みはController側）。
     * 承認待ちでなくなっていた申請（既に承認/却下/取消済み）はスキップし、バッチ全体は失敗させない。
     *
     * @param  Collection<int, TrainingRequest>  $trainingRequests
     * @return array{approved: int}
     */
    public function execute(Collection $trainingRequests, Employee $decidedBy): array
    {
        $approved = 0;

        foreach ($trainingRequests as $trainingRequest) {
            try {
                $this->approveTrainingRequestAction->execute($trainingRequest, $decidedBy);
                $approved++;
            } catch (TrainingRequestNotPendingException) {
                // 一括操作のため、他の申請の承認が既に反映されている等で承認待ちでなくなっていても、
                // その1件をスキップして続行する。
            }
        }

        return ['approved' => $approved];
    }
}
