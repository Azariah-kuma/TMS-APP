<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use App\Models\TrainingEnrollment;
use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingEnrollment(研修受講)の一括作成リクエストのバリデーションを行うフォーム。
 * department_id を省略すると、在籍中の全従業員（全社）が対象になる。
 */
final class BulkEnrollTrainingRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('create', TrainingEnrollment::class);
    }

    protected function prepareForValidation(): void
    {
        $this->castIdFieldsToInt('department_id');
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'due_at' => ['nullable', 'date'],
        ];
    }
}
