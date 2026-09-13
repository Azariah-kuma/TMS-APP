<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * 人事・研修管理の主要モデルへの汎用監査ログObserver。AppServiceProviderで対象モデルに登録する。
 * 異動（EmployeeAssignment）・委任（Delegation）は専用の履歴テーブルを既に持つため対象外。
 */
final class AuditLogObserver
{
    private const array EXCLUDED_KEYS = ['created_at', 'updated_at'];

    public function created(Model $model): void
    {
        $this->record($model, AuditLogAction::Created, $this->withoutExcludedKeys($model->getAttributes()));
    }

    public function updated(Model $model): void
    {
        $changes = $this->withoutExcludedKeys($model->getChanges());

        if ($changes === []) {
            return;
        }

        $this->record($model, AuditLogAction::Updated, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, AuditLogAction::Deleted, $this->withoutExcludedKeys($model->getAttributes()));
    }

    /** @param  array<string, mixed>  $changes */
    private function record(Model $model, AuditLogAction $action, array $changes): void
    {
        AuditLog::create([
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'action' => $action,
            'actor_employee_id' => Auth::user()?->employee?->id,
            'changes' => $changes,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function withoutExcludedKeys(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(self::EXCLUDED_KEYS));
    }
}
