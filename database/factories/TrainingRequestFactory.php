<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrainingRequestStatus;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修受講申請のファクトリクラス。
 */

/** @extends Factory<TrainingRequest> */
class TrainingRequestFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            // デフォルトは本人申請（employee_idと同じ従業員）。
            // 代理申請を表すテストは'requested_by_employee_id' を明示的に上書きすること。
            'requested_by_employee_id' => fn (array $attributes) => $attributes['employee_id'],
            'training_id' => Training::factory(),
            'status' => TrainingRequestStatus::Pending,
            'reason' => fake()->sentence(),
            'due_at' => null,
            'required_approval_stages' => 1,
            'current_approval_stage' => 1,
            'decided_by_employee_id' => null,
            'decided_at' => null,
            'decision_comment' => null,
            'training_enrollment_id' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainingRequestStatus::Approved,
            'decided_by_employee_id' => Employee::factory(),
            'decided_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainingRequestStatus::Rejected,
            'decided_by_employee_id' => Employee::factory(),
            'decided_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainingRequestStatus::Cancelled,
        ]);
    }

    /** 多段階承認が必要な研修への申請を表す（デフォルトでは1段階目が保留中）。 */
    public function multistage(int $requiredStages = 2): static
    {
        return $this->state(fn (array $attributes) => [
            'required_approval_stages' => $requiredStages,
            'current_approval_stage' => 1,
        ]);
    }
}
