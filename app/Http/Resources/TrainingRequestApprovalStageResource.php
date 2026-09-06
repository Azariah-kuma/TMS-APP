<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingRequestApprovalStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * TrainingRequestApprovalStage(多段階承認の各段階の決裁記録)のリソースクラス
 */

/** @mixin TrainingRequestApprovalStage */
final class TrainingRequestApprovalStageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'stage_number' => $this->stage_number,
            'decided_by_name' => $this->whenLoaded('decidedBy', fn () => $this->decidedBy->user->name),
            'status' => $this->status->value,
            'decided_at' => $this->decided_at->toISOString(),
            'comment' => $this->comment,
        ];
    }
}
