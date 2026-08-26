<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Models\Position;
use Illuminate\Support\Facades\DB;

final class InsertPositionAction
{
    /**
     * 新しい役職を、指定した役職の直後（＝1つ下の序列）に挿入する。
     * 挿入位置以降の役職は、既存の並びを保ったままrankを1つずつ繰り下げる。
     */
    public function execute(string $name, string $code, ?int $afterPositionId): Position
    {
        return DB::transaction(function () use ($name, $code, $afterPositionId) {
            $insertRank = 1;

            if ($afterPositionId !== null) {
                $after = Position::query()->lockForUpdate()->findOrFail($afterPositionId);
                $insertRank = $after->rank + 1;
            }

            Position::query()->where('rank', '>=', $insertRank)->increment('rank');

            return Position::create([
                'name' => $name,
                'code' => $code,
                'rank' => $insertRank,
            ]);
        });
    }
}
