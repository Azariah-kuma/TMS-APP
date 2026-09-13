<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Models\TrainingFeedback;
use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingFeedback(研修効果測定のアンケート・テスト提出)のバリデーションを行うフォーム
 */
final class StoreTrainingFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [TrainingFeedback::class, $this->route('trainingEnrollment')]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'satisfaction_score' => ['required', 'integer', 'min:1', 'max:5'],
            'understanding_score' => ['required', 'integer', 'min:1', 'max:5'],
            'quiz_score' => ['nullable', 'integer', 'min:0', 'max:100'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
