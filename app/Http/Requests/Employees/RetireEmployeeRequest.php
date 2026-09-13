<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

/*
 * Employee(従業員)の退職登録リクエストのバリデーションを行うフォーム
 */
final class RetireEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('retire', $this->route('employee'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'retired_at' => ['required', 'date'],
        ];
    }
}
