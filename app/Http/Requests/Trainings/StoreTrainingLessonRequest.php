<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingLesson(研修Lesson)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreTrainingLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('training'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
