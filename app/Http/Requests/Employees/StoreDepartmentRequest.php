<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Department(部署)の作成リクエストのバリデーションを行うフォーム
 */
final class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:departments,code'],
        ];
    }
}
