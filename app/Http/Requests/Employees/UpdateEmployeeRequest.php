<?php

declare(strict_types=1);

namespace App\Http\Requests\Employees;

use Illuminate\Foundation\Http\FormRequest;

/*
 * Employee(従業員)の氏名訂正リクエストのバリデーションを行うフォーム。
 * 婚姻等による姓の変更を想定し、氏名・フリガナのみを対象とする。
 */
final class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('employee'));
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'last_name' => ['required', 'string', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name_kana' => ['required', 'string', 'max:255', 'regex:/\A[\x{30A0}-\x{30FF}]+\z/u'],
            'first_name_kana' => ['required', 'string', 'max:255', 'regex:/\A[\x{30A0}-\x{30FF}]+\z/u'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'last_name_kana.regex' => '姓（フリガナ）はカタカナで入力してください。',
            'first_name_kana.regex' => '名（フリガナ）はカタカナで入力してください。',
        ];
    }
}
