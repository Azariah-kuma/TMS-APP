<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Models\Delegation;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Delegation(委任)の作成リクエストのバリデーションを行うフォームリクエストクラス。
 */
final class StoreDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Delegation::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'delegate_id' => ['required', 'integer', 'exists:employees,id'],
            'started_at' => ['required', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
        ];
    }
}
