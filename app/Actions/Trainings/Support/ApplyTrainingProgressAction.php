<?php

declare(strict_types=1);

namespace App\Actions\Trainings\Support;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\TrainingEnrollment;
use Illuminate\Support\Facades\Date;

/**
 * 進捗率（0〜100）を受け取り、ステータスと日時を整合させて保存する処理。
 */
final class ApplyTrainingProgressAction
{
    /**
     * 0: 未着手／1〜99: 受講中（初回のみ started_at を記録）／100: 完了。
     */
    public function execute(TrainingEnrollment $enrollment, int $progress): TrainingEnrollment
    {
        $progress = max(0, min(100, $progress));

        $status = match (true) {
            $progress <= 0 => TrainingEnrollmentStatus::NotStarted,
            $progress >= 100 => TrainingEnrollmentStatus::Completed,
            default => TrainingEnrollmentStatus::InProgress,
        };

        $enrollment->fill([
            'progress' => $progress,
            'status' => $status,
            'started_at' => $enrollment->started_at ?? ($progress > 0 ? Date::now() : null),
            'completed_at' => $status === TrainingEnrollmentStatus::Completed ? Date::now() : null,
        ]);

        $enrollment->save();

        return $enrollment;
    }
}
