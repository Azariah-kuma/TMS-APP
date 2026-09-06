<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 研修カタログに閲覧対象者の制限を追加する。
 *
 * 3つの条件（対象部署／管理職／今年度入社の新入社員）はいずれもOR条件で、
 * 1つも設定されていなければ「全員」に公開される（既存の研修はこの状態のまま維持される）。
 * 人事は Gate::before により、対象者の設定に関わらず常に全ての研修を閲覧できる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->foreignId('audience_department_id')->nullable()->after('is_active')
                ->constrained('departments')->nullOnDelete();
            $table->boolean('audience_managers_only')->default(false)->after('audience_department_id');
            $table->boolean('audience_new_hires_only')->default(false)->after('audience_managers_only');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('audience_department_id');
            $table->dropColumn(['audience_managers_only', 'audience_new_hires_only']);
        });
    }
};
