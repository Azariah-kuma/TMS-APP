<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * Employee(従業員)のリソースクラス
 */

/** @mixin Employee */
final class EmployeeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_code' => $this->employee_code,
            'name' => $this->user->name,
            'email' => $this->user->email,
            'role' => $this->role->value,
            'hired_at' => $this->hired_at?->toDateString(),
            'retired_at' => $this->retired_at?->toDateString(),
            'current_assignment' => new EmployeeAssignmentResource($this->whenLoaded('currentAssignment')),
        ];
    }
}
