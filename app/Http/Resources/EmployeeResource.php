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
            'name_kana' => $this->user->nameKana,
            'last_name' => $this->user->last_name,
            'first_name' => $this->user->first_name,
            'last_name_kana' => $this->user->last_name_kana,
            'first_name_kana' => $this->user->first_name_kana,
            'email' => $this->user->email,
            'role' => $this->role->value,
            'hired_at' => $this->hired_at?->toDateString(),
            'retired_at' => $this->retired_at?->toDateString(),
            // 実際に直属の部下を持っているか。
            // 新規オンボーディング直後は必ずfalse。
            'is_manager' => (bool) ($this->is_manager ?? false),
            'current_assignment' => new EmployeeAssignmentResource($this->whenLoaded('currentAssignment')),
        ];
    }
}
