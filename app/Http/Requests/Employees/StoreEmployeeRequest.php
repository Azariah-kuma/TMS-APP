<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

/*
 * Employee(従業員)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'employee_code' => ['required', 'string', 'max:50', 'unique:employees,employee_code'],
            'role' => ['required', new Enum(EmployeeRole::class)],
            'hired_at' => ['required', 'date'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
        ];
    }
}
