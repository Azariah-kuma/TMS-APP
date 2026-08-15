<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Models\Training;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Training(研修)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Training::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
