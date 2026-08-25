<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 「誰が申請したか」を記録する列を追加する。
 * 本人申請なら employee_id と同じ値、上司による代理申請ならその上司の従業員IDになる。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->foreignId('requested_by_employee_id')->nullable()->after('employee_id')
                ->constrained('employees')->nullOnDelete();

            $table->index('requested_by_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('training_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requested_by_employee_id');
        });
    }
};
