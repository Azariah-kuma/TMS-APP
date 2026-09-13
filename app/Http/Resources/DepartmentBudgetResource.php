<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\DepartmentBudget;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * DepartmentBudget(部署の年度研修予算)のリソースクラス
 */

/** @mixin DepartmentBudget */
final class DepartmentBudgetResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'department_id' => $this->department_id,
            'department_name' => $this->whenLoaded('department', fn () => $this->department->name),
            'fiscal_year' => $this->fiscal_year,
            'budget_amount' => (float) $this->budget_amount,
        ];
    }
}
