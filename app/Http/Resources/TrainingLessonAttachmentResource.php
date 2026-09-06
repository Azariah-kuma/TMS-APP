<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingLessonAttachment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/*
 * TrainingLessonAttachment(研修Lessonの添付教材ファイル)のリソースクラス
 */

/** @mixin TrainingLessonAttachment */
final class TrainingLessonAttachmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'url' => Storage::disk('public')->url($this->path),
            'original_name' => $this->original_name,
            'mime_type' => $this->mime_type,
        ];
    }
}
