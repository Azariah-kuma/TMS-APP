<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use App\Models\Training;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
 * Training(研修)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreTrainingRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('create', Training::class);
    }

    /**
     * HTMLの<select>は値を常に文字列として送るため、後続の保存処理が
     * 要求する厳密な int|null 型に合わせてここで変換しておく。
     * 対象者フラグは、作成直後のレスポンスがDBのデフォルト値（false）と
     * 一致するよう、未指定時もfalseとして明示的に確定させておく。
     */
    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('audience_department_id', 'approval_stage_count');

        $requiresMultistageApproval = $this->boolean('requires_multistage_approval');

        $this->merge([
            'audience_managers_only' => $this->boolean('audience_managers_only'),
            'audience_new_hires_only' => $this->boolean('audience_new_hires_only'),
            'requires_multistage_approval' => $requiresMultistageApproval,
            // フラグがOFFなら、送られてきた値に関わらず段階数は保持しない。
            'approval_stage_count' => $requiresMultistageApproval ? $this->input('approval_stage_count') : null,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'audience_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'audience_managers_only' => ['boolean'],
            'audience_new_hires_only' => ['boolean'],
            'requires_multistage_approval' => ['boolean'],
            'approval_stage_count' => [
                Rule::requiredIf(fn () => $this->boolean('requires_multistage_approval')),
                'nullable',
                'integer',
                'min:2',
                'max:5',
            ],
        ];
    }
}
