<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Models\TrainingEnrollment;
use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingEnrollment(研修受講)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreTrainingEnrollmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', TrainingEnrollment::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'training_id' => ['required', 'integer', 'exists:trainings,id'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
