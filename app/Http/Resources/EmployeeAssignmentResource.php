<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\EmployeeAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * EmployeeAssignment(従業員の部署・役職の割り当て)のリソースクラス
 */

/** @mixin EmployeeAssignment */
final class EmployeeAssignmentResource extends JsonResource
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
            'position_id' => $this->position_id,
            'position_name' => $this->whenLoaded('position', fn () => $this->position->name),
            'manager_id' => $this->manager_id,
            'started_at' => $this->started_at?->toDateString(),
            'ended_at' => $this->ended_at?->toDateString(),
            'is_active' => $this->isActive(),
        ];
    }
}
