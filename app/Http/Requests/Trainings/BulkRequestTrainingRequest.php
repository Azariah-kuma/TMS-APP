<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use App\Http\Requests\Concerns\CastsIdFieldsToInt;
use App\Models\TrainingRequest;
use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingRequest(研修受講申請)の部署一括作成リクエストのバリデーションを行うフォーム。
 * 対象従業員の絞り込み（実行者の部下のみ、HRなら部署内全員）はAction側で行う。
 */
final class BulkRequestTrainingRequest extends FormRequest
{
    use CastsIdFieldsToInt;

    public function authorize(): bool
    {
        return $this->user()->can('bulkCreate', TrainingRequest::class);
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
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ];
    }
}
