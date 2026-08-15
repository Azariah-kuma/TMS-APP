<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use Illuminate\Foundation\Http\FormRequest;

/*
 * Training(研修)の更新リクエストのバリデーションを行うフォーム
 */
final class UpdateTrainingRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
