<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingLesson(研修Lesson)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreTrainingLessonRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('training'));
    }

    /**
     * HTMLの<select>や multipart/form-data は値を常に文字列として送るため、
     * 後続のActionが要求する厳密な int|null 型に合わせてここで変換しておく。
     */
    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('position');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'position' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'content' => [
                'nullable',
                'file',
                'max:102400',
                'mimes:mp4,mov,webm,m4v,pdf,ppt,pptx,doc,docx,png,jpg,jpeg',
            ],
        ];
    }
}
