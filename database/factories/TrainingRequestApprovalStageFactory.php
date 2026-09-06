<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrainingRequestApprovalStageStatus;
use App\Models\Employee;
use App\Models\TrainingRequest;
use App\Models\TrainingRequestApprovalStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 多段階承認の段階別決裁記録のファクトリクラス。
 */

/** @extends Factory<TrainingRequestApprovalStage> */
class TrainingRequestApprovalStageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_request_id' => TrainingRequest::factory(),
            'stage_number' => 1,
            'decided_by_employee_id' => Employee::factory(),
            'status' => TrainingRequestApprovalStageStatus::Approved,
            'decided_at' => now(),
            'comment' => null,
        ];
    }
}
