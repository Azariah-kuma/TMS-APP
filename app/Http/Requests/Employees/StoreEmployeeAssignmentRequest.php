<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Employee(従業員)の異動リクエストのバリデーションを行うフォーム
 */
final class StoreEmployeeAssignmentRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('transfer', $this->route('employee'));
    }

    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('department_id', 'position_id', 'manager_id');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'manager_id' => ['nullable', 'integer', 'exists:employees,id'],
            'started_at' => ['required', 'date'],
        ];
    }
}
