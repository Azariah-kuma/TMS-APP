<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 研修1件・受講者1名あたりの費用（予算管理・研修効果測定のROI算出に使用）。
 * 未設定（null）はコスト管理の対象外として扱う。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->decimal('unit_cost', 10, 2)->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('trainings', function (Blueprint $table) {
            $table->dropColumn('unit_cost');
        });
    }
};
