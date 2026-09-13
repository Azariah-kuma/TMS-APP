<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingFeedback;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * TrainingFeedback(研修効果測定のアンケート・テスト提出)のリソースクラス
 */

/** @mixin TrainingFeedback */
final class TrainingFeedbackResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training_enrollment_id' => $this->training_enrollment_id,
            'satisfaction_score' => $this->satisfaction_score,
            'understanding_score' => $this->understanding_score,
            'quiz_score' => $this->quiz_score,
            'comment' => $this->comment,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
        ];
    }
}
