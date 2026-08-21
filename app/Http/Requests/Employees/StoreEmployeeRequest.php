<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Enums\EmployeeRole;
use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/*
 * Employee(従業員)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreEmployeeRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('department_id', 'position_id', 'manager_id');
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name_kana' => ['required', 'string', 'max:255', 'regex:/\A[\x{30A0}-\x{30FF}]+\z/u'],
            'first_name_kana' => ['required', 'string', 'max:255', 'regex:/\A[\x{30A0}-\x{30FF}]+\z/u'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'role' => ['required', new Enum(EmployeeRole::class)],
            'hired_at' => ['required', 'date'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'last_name_kana.regex' => '姓（フリガナ）はカタカナで入力してください。',
            'first_name_kana.regex' => '名（フリガナ）はカタカナで入力してください。',
        ];
    }
}
