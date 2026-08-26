<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
 * Position(役職)の更新リクエストのバリデーションを行うフォーム。
 * 誤登録の訂正用。
 */
final class UpdatePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('position'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('positions', 'code')->ignore($this->route('position'))],
        ];
    }
}
