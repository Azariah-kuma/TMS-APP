<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;

/*
 * Position(役職)の作成リクエストのバリデーションを行うフォーム
 */
final class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Position::class);
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:positions,code'],
            'rank' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
