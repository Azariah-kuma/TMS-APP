<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 単一ファイルだった教材（content_path等）を、複数ファイル添付に対応した
 * training_lesson_attachments へ移行する。既存データはそのまま1件の添付として引き継ぐ。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('training_lessons')
            ->whereNotNull('content_path')
            ->orderBy('id')
            ->get(['id', 'content_path', 'content_original_name', 'content_mime_type'])
            ->each(function (object $lesson): void {
                DB::table('training_lesson_attachments')->insert([
                    'training_lesson_id' => $lesson->id,
                    'path' => $lesson->content_path,
                    'original_name' => $lesson->content_original_name,
                    'mime_type' => $lesson->content_mime_type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('training_lessons', function (Blueprint $table) {
            $table->dropColumn(['content_path', 'content_original_name', 'content_mime_type']);
        });
    }

    public function down(): void
    {
        // スキーマのみ復元する（移行済みデータの再統合は行わない）。
        Schema::table('training_lessons', function (Blueprint $table) {
            $table->string('content_path')->nullable()->after('position');
            $table->string('content_original_name')->nullable()->after('content_path');
            $table->string('content_mime_type')->nullable()->after('content_original_name');
        });
    }
};
