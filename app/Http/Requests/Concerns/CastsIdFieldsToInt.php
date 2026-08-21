<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

/**
 * HTMLの<select>は選択値を常に文字列として送るため、対象フィールドを
 * バリデーション前に int（未入力ならnull）へ変換しておくためのトレイト。
 *
 * 後続のActionが要求する厳密な int 型（declare(strict_types=1)）に合わせる必要がある。
 */
trait CastsIdFieldsToInt
{
    protected function castIdFieldsToInt(string ...$fields): void
    {
        $this->merge(array_combine(
            $fields,
            array_map(
                fn (string $field) => $this->filled($field) ? (int) $this->input($field) : null,
                $fields,
            ),
        ));
    }
}
