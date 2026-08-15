<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrainingLessonCompletionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * 従業員1名・研修1件・Lesson1件に対する完了記録のモデルクラス。
 */
#[Fillable(['training_enrollment_id', 'training_lesson_id', 'completed_at'])]
class TrainingLessonCompletion extends Model
{
    /** @use HasFactory<TrainingLessonCompletionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
        ];
    }

    public function trainingEnrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class);
    }

    public function trainingLesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class);
    }
}
