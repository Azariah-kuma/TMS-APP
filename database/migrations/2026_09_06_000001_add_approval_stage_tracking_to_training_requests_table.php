<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 研修申請ごとに、必要な承認段階数と現在の段階を記録する。
 *
 * required_approval_stages は申請作成時点のTraining.approval_stage_countのスナップショット
 * （1＝従来通りの単層承認）。作成後にTraining側の設定が変わっても、既に進行中の申請には影響しない。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('required_approval_stages')->default(1)->after('training_id');
            $table->unsignedTinyInteger('current_approval_stage')->default(1)->after('required_approval_stages');
        });
    }

    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropColumn(['required_approval_stages', 'current_approval_stage']);
        });
    }
};
