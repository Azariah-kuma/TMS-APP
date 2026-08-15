<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Exceptions\InvalidDelegationException;
use App\Models\Delegation;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class CreateDelegationAction
{
    /**
     * $delegator の上司権限（部下の参照権限）を、期間限定で $delegate に委任する。
     */
    public function execute(
        Employee $delegator,
        Employee $delegate,
        Carbon $startedAt,
        ?Carbon $endedAt,
    ): Delegation {
        if ($delegate->is($delegator)) {
            throw new InvalidDelegationException('自分自身に権限を委任することはできません。');
        }

        if ($endedAt !== null && $endedAt->lt($startedAt)) {
            throw new InvalidDelegationException('委任の終了日は、開始日以降に設定してください。');
        }

        $overlapping = $delegator->delegationsGiven()
            ->where('delegate_id', $delegate->id)
            ->where(fn (Builder $query) => $query->whereNull('ended_at')->orWhere('ended_at', '>=', $startedAt))
            ->when($endedAt !== null, fn (Builder $query) => $query->where('started_at', '<=', $endedAt))
            ->exists();

        if ($overlapping) {
            throw new InvalidDelegationException('同じ組み合わせで期間が重複する委任が既に存在します。');
        }

        return $delegator->delegationsGiven()->create([
            'delegate_id' => $delegate->id,
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
        ]);
    }
}
