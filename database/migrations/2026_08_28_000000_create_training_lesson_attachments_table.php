<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lessonの教材ファイルを複数添付できるようにする（1Lesson: Nファイル）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_lesson_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_lesson_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_lesson_attachments');
    }
};
