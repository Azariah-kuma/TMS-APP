<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\TrainingFeedbackPolicy;
use Database\Factories\TrainingFeedbackFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 受講完了後に本人が提出する、研修効果測定（満足度・理解度アンケート＋簡易テスト）の記録。
 */
#[Fillable(['training_enrollment_id', 'satisfaction_score', 'understanding_score', 'quiz_score', 'comment', 'submitted_at'])]
#[UsePolicy(TrainingFeedbackPolicy::class)]
class TrainingFeedback extends Model
{
    /** @use HasFactory<TrainingFeedbackFactory> */
    use HasFactory;

    /** "feedback"は英語の不可算名詞のためStr::pluralが単数形のままになる。テーブル名を明示する。 */
    protected $table = 'training_feedbacks';

    protected function casts(): array
    {
        return [
            'satisfaction_score' => 'integer',
            'understanding_score' => 'integer',
            'quiz_score' => 'integer',
            'submitted_at' => 'datetime',
        ];
    }

    public function trainingEnrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class);
    }
}
