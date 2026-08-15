<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Training;
use App\Models\TrainingLesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修レッスンのファクトリクラス。
 */

/** @extends Factory<TrainingLesson> */
class TrainingLessonFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_id' => Training::factory(),
            'title' => fake()->sentence(4),
            'position' => 0,
        ];
    }
}
