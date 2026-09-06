<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrainingLessonAttachmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * 研修Lessonに添付された教材ファイル1件のモデルクラス。
 */
#[Fillable(['training_lesson_id', 'path', 'original_name', 'mime_type'])]
class TrainingLessonAttachment extends Model
{
    /** @use HasFactory<TrainingLessonAttachmentFactory> */
    use HasFactory;

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(TrainingLesson::class, 'training_lesson_id');
    }
}
