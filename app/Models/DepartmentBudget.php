<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\DepartmentBudgetPolicy;
use Database\Factories\DepartmentBudgetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * 部署の年度研修予算のモデルクラス。
 */
#[Fillable(['department_id', 'fiscal_year', 'budget_amount'])]
#[UsePolicy(DepartmentBudgetPolicy::class)]
class DepartmentBudget extends Model
{
    /** @use HasFactory<DepartmentBudgetFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'budget_amount' => 'decimal:2',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
