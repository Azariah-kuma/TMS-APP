<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Training;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * Training(研修)のリソースクラス
 */

/** @mixin Training */
final class TrainingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'is_active' => $this->is_active,
            'audience_department_id' => $this->audience_department_id,
            'audience_department_name' => $this->whenLoaded(
                'audienceDepartment',
                fn () => $this->audienceDepartment?->name,
            ),
            'audience_managers_only' => $this->audience_managers_only,
            'audience_new_hires_only' => $this->audience_new_hires_only,
            'requires_multistage_approval' => $this->requires_multistage_approval,
            'approval_stage_count' => $this->approval_stage_count,
            'lessons' => TrainingLessonResource::collection($this->whenLoaded('lessons')),
        ];
    }
}
