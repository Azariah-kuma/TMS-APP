<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AuditLogAction;
use App\Policies\AuditLogPolicy;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 人事・研修管理の主要モデルに対する変更操作の監査ログ。1行が1回の作成・更新・削除に対応する。
 * 更新日時の概念を持たない（記録後に変更されることがないため）ためupdated_atは使わない。
 */
#[Fillable(['auditable_type', 'auditable_id', 'action', 'actor_employee_id', 'changes'])]
#[UsePolicy(AuditLogPolicy::class)]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'action' => AuditLogAction::class,
            'changes' => 'array',
        ];
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'actor_employee_id');
    }
}
