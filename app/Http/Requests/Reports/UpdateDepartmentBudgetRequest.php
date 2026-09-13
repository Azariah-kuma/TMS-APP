<?php

declare(strict_types=1);

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;

/*
 * DepartmentBudget(部署の年度研修予算)の更新リクエストのバリデーションを行うフォーム。
 * 金額の訂正のみを想定し、部署・年度は変更できない（変更したい場合は削除して作り直す）。
 */
final class UpdateDepartmentBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('departmentBudget'));
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'budget_amount' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
        ];
    }
}
