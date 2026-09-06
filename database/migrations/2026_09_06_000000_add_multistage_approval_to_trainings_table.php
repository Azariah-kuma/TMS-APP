<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 高額・重要な研修向けに、多段階承認（例: 部長→役員）を必須にできるようにする。
 *
 * requires_multistage_approval が true の研修は、閲覧対象者の設定に関わらず
 * 常に管理職にも表示される（申請が来た際に承認者になり得るため、事前に把握できるようにする）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->boolean('requires_multistage_approval')->default(false)->after('audience_new_hires_only');
            $table->unsignedTinyInteger('approval_stage_count')->nullable()->after('requires_multistage_approval');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn(['requires_multistage_approval', 'approval_stage_count']);
        });
    }
};
