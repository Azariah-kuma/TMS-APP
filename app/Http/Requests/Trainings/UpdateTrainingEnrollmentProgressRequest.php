<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingEnrollment(研修受講)の進捗更新リクエストのバリデーションを行うフォーム
 */
final class UpdateTrainingEnrollmentProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('trainingEnrollment'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'progress' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }
}
