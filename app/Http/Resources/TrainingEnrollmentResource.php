<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingEnrollment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * TrainingEnrollment(研修受講)のリソースクラス
 */

/** @mixin TrainingEnrollment */
final class TrainingEnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->whenLoaded('employee', fn () => $this->employee->user->name),
            'training' => new TrainingResource($this->whenLoaded('training')),
            'status' => $this->status->value,
            'progress' => $this->progress,
            'due_at' => $this->due_at?->toDateString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'completed_lesson_ids' => $this->whenLoaded(
                'lessonCompletions',
                fn () => $this->lessonCompletions->pluck('training_lesson_id')->values(),
            ),
        ];
    }
}
