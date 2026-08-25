<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use App\Models\Employee;
use App\Models\TrainingRequest;
use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingRequest(研修受講申請)の作成リクエストのバリデーションを行うフォーム。
 * employee_id を省略するとログイン中の従業員自身の申請になる。
 * employee_id を指定した場合（部下の代理申請）は、その従業員の上司のみ許可される。
 */
final class StoreTrainingRequestRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        $forEmployee = $this->filled('employee_id') ? Employee::find($this->integer('employee_id')) : null;

        return $this->user()->can('create', [TrainingRequest::class, $forEmployee]);
    }

    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('employee_id');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'training_id' => ['required', 'integer', 'exists:trainings,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
