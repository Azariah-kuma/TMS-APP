<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TrainingLesson;
use App\Models\TrainingLessonAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修Lessonの添付教材ファイルのファクトリクラス。
 */

/** @extends Factory<TrainingLessonAttachment> */
class TrainingLessonAttachmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'training_lesson_id' => TrainingLesson::factory(),
            'path' => 'training-lessons/'.fake()->uuid().'.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
        ];
    }
}
