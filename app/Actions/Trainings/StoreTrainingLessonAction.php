<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Models\Training;
use App\Models\TrainingLesson;
use Illuminate\Http\UploadedFile;

final class StoreTrainingLessonAction
{
    /**
     * 研修にLesson（教材）を追加する。$content が渡された場合は、動画・PDF等の
     * 教材本体ファイルを public ディスクに保存し、Lessonに紐付ける。
     */
    public function execute(
        Training $training,
        string $title,
        ?int $position,
        ?UploadedFile $content,
    ): TrainingLesson {
        $attributes = ['title' => $title];

        if ($position !== null) {
            $attributes['position'] = $position;
        }

        if ($content !== null) {
            $attributes['content_path'] = $content->store('training-lessons', 'public');
            $attributes['content_original_name'] = $content->getClientOriginalName();
            $attributes['content_mime_type'] = $content->getMimeType();
        }

        return $training->lessons()->create($attributes);
    }
}
