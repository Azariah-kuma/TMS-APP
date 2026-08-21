<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lessonの教材本体（動画・PDF等）を1ファイル添付できるようにする。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->string('content_path')->nullable()->after('position');
            $table->string('content_original_name')->nullable()->after('content_path');
            $table->string('content_mime_type')->nullable()->after('content_original_name');
        });
    }

    public function down(): void
    {
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->dropColumn(['content_path', 'content_original_name', 'content_mime_type']);
        });
    }
};
