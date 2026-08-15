<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use App\Models\TrainingLessonCompletion;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修レッスン完了のファクトリクラス。
 */

/** @extends Factory<TrainingLessonCompletion> */
class TrainingLessonCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_enrollment_id' => TrainingEnrollment::factory(),
            'training_lesson_id' => TrainingLesson::factory(),
            'completed_at' => now(),
        ];
    }
}
