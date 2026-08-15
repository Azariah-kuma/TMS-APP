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
            'lessons' => TrainingLessonResource::collection($this->whenLoaded('lessons')),
        ];
    }
}
