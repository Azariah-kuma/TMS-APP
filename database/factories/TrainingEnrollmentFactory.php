<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修受講のファクトリクラス。
 */

/** @extends Factory<TrainingEnrollment> */
class TrainingEnrollmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'training_id' => Training::factory(),
            'status' => TrainingEnrollmentStatus::NotStarted,
            'progress' => 0,
            'due_at' => fake()->dateTimeBetween('now', '+3 months'),
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
