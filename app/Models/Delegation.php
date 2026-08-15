<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\DelegationPolicy;
use Database\Factories\DelegationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 社員間の権限委任を管理するモデルクラス。
 *
 * delegator_id: 委任元の社員
 * delegate_id: 委任先の社員
 * started_at / ended_at: 委任期間
 */
#[Fillable(['delegator_id', 'delegate_id', 'started_at', 'ended_at'])]
#[UsePolicy(DelegationPolicy::class)]
class Delegation extends Model
{
    /** @use HasFactory<DelegationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    /** 権限を委任した側（本来の上司）。 */
    public function delegator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegator_id');
    }

    /** 権限を委任された側（代理者）。 */
    public function delegate(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'delegate_id');
    }

    public function isActive(): bool
    {
        $today = Carbon::today();

        return ! $this->started_at->gt($today) && ($this->ended_at === null || ! $this->ended_at->lt($today));
    }

    /** 現在有効な（started_at <= 今日 <= ended_at、または ended_at が未設定の）委任に絞り込む。 */
    #[Scope]
    protected function active(Builder $query): void
    {
        $today = Carbon::today()->toDateString();

        $query->where('started_at', '<=', $today)
            ->where(function (Builder $query) use ($today): void {
                $query->whereNull('ended_at')->orWhere('ended_at', '>=', $today);
            });
    }
}
