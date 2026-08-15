<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Models\Delegation;
use Illuminate\Support\Carbon;

final class RevokeDelegationAction
{
    /**
     * 委任を即時終了させる。
     */
    public function execute(Delegation $delegation): Delegation
    {
        $yesterday = Carbon::yesterday();
        $newEndedAt = $yesterday->lt($delegation->started_at) ? $delegation->started_at->copy() : $yesterday;

        if ($delegation->ended_at === null || $delegation->ended_at->gt($newEndedAt)) {
            $delegation->update(['ended_at' => $newEndedAt]);
        }

        return $delegation;
    }
}
