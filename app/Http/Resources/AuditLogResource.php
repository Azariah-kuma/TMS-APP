<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * AuditLog(監査ログ)のリソースクラス
 */

/** @mixin AuditLog */
final class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'auditable_type' => class_basename($this->auditable_type),
            'auditable_id' => $this->auditable_id,
            'action' => $this->action->value,
            'actor_employee_id' => $this->actor_employee_id,
            'actor_name' => $this->whenLoaded('actor', fn () => $this->actor?->user?->name),
            'changes' => $this->changes,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
