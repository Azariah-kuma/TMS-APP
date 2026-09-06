<?php

declare(strict_types=1);

namespace App\Actions\Trainings;

use App\Models\Training;
use App\Models\TrainingLesson;
use Illuminate\Http\UploadedFile;

final class StoreTrainingLessonAction
{
    /**
     * 研修にLesson（教材）を追加する。$contents が渡された場合は、動画・PDF等の
     * 教材本体ファイルを public ディスクに保存し、複数までLessonに紐付ける。
     *
     * @param  list<UploadedFile>  $contents
     */
    public function execute(
        Training $training,
        string $title,
        ?int $position,
        array $contents = [],
    ): TrainingLesson {
        $attributes = ['title' => $title];

        if ($position !== null) {
            $attributes['position'] = $position;
        }

        $lesson = $training->lessons()->create($attributes);

        foreach ($contents as $content) {
            $lesson->attachments()->create([
                'path' => $content->store('training-lessons', 'public'),
                'original_name' => $content->getClientOriginalName(),
                'mime_type' => $content->getMimeType(),
            ]);
        }

        return $lesson->load('attachments');
    }
}
