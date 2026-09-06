<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Training(研修)の更新リクエストのバリデーションを行うフォーム
 */
final class UpdateTrainingRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('training'));
    }

    /**
     * HTMLの<select>は値を常に文字列として送るため、後続の保存処理が
     * 要求する厳密な int|null 型に合わせてここで変換しておく。
     */
    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('audience_department_id', 'approval_stage_count');
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'audience_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'audience_managers_only' => ['sometimes', 'boolean'],
            'audience_new_hires_only' => ['sometimes', 'boolean'],
            'requires_multistage_approval' => ['sometimes', 'boolean'],
            'approval_stage_count' => ['sometimes', 'nullable', 'integer', 'min:2', 'max:5'],
        ];
    }
}
