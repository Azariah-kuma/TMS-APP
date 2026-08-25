<?php

declare(strict_types=1);

namespace App\Http\Requests\Trainings;

use Illuminate\Foundation\Http\FormRequest;

/*
 * TrainingRequest(研修受講申請)の一括承認リクエストのバリデーションを行うフォーム。
 */
final class BulkApproveTrainingRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->employee !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:training_requests,id'],
        ];
    }
}
