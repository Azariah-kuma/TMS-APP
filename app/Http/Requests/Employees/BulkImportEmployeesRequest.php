<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Employee(従業員)のCSV一括登録リクエストのバリデーションを行うフォーム。
 * CSVの内容自体（各行のデータ）はBulkImportEmployeesAction側で行を単位に検証する。
 */
final class BulkImportEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Employee::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ];
    }
}
