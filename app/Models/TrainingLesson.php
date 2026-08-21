<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TrainingLessonFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * 研修のLessonのモデルクラス。
 */
#[Fillable(['training_id', 'title', 'position', 'content_path', 'content_original_name', 'content_mime_type'])]
class TrainingLesson extends Model
{
    /** @use HasFactory<TrainingLessonFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function completions(): HasMany
    {
        return $this->hasMany(TrainingLessonCompletion::class);
    }
}
