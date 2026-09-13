<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use App\Models\DepartmentBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/*
 * DepartmentBudget(部署の年度研修予算)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreDepartmentBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DepartmentBudget::class);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'fiscal_year' => [
                'required',
                'integer',
                'min:2000',
                'max:2100',
                Rule::unique('department_budgets')->where(fn ($query) => $query->where('department_id', $this->input('department_id'))),
            ],
            'budget_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }
}
