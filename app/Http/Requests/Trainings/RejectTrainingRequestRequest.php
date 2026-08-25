<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingRequest(研修受講申請)の却下リクエストのバリデーションを行うフォーム。
 */
final class RejectTrainingRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reject', $this->route('trainingRequest'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
