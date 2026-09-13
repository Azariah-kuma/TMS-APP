<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TrainingEnrollment;
use App\Models\TrainingFeedback;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修効果測定（アンケート・テスト提出）のファクトリクラス。
 */

/** @extends Factory<TrainingFeedback> */
class TrainingFeedbackFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_enrollment_id' => TrainingEnrollment::factory(),
            'satisfaction_score' => fake()->numberBetween(1, 5),
            'understanding_score' => fake()->numberBetween(1, 5),
            'quiz_score' => fake()->numberBetween(0, 100),
            'comment' => fake()->optional()->sentence(),
            'submitted_at' => now(),
        ];
    }
}
