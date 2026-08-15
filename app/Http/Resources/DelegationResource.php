<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Delegation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * Delegation(委任)のリソースクラス
 */

/** @mixin Delegation */
final class DelegationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delegator_id' => $this->delegator_id,
            'delegate_id' => $this->delegate_id,
            'delegate_name' => $this->whenLoaded('delegate', fn () => $this->delegate->user->name),
            'started_at' => $this->started_at?->toDateString(),
            'ended_at' => $this->ended_at?->toDateString(),
            'is_active' => $this->isActive(),
        ];
    }
}
