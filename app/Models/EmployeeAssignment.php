<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\EmployeeAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 従業員の所属部署・役職・上司の割り当て履歴のモデルクラス。
 *
 * ended_at が null のレコードが現在の状態を表す。
 * 異動や昇格時は新しいレコードを追加し、以前のレコードを終了扱いにする。
 */
#[Fillable(['employee_id', 'department_id', 'position_id', 'manager_id', 'started_at', 'ended_at'])]
class EmployeeAssignment extends Model
{
    /** @use HasFactory<EmployeeAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function isActive(): bool
    {
        return $this->ended_at === null;
    }
}
