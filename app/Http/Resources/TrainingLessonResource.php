<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingLesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/*
 * TrainingLesson(研修Lesson)のリソースクラス
 */

/** @mixin TrainingLesson */
final class TrainingLessonResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'training_id' => $this->training_id,
            'title' => $this->title,
            'position' => $this->position,
        ];
    }
}
