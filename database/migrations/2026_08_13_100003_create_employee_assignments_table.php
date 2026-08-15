<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 従業員の部署・役職・上司の割り当て履歴。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->foreignId('position_id')->constrained()->restrictOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'started_at']);
            $table->index(['manager_id', 'ended_at']);
        });

        // 1人の従業員に「現在有効な割り当て」（ended_at IS NULL）は
        // 同時に1件しか存在できないことをDBレベルで保証する。
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX employee_assignments_one_active_per_employee
            ON employee_assignments (employee_id)
            WHERE ended_at IS NULL
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE employee_assignments
            ADD CONSTRAINT employee_assignments_period_valid
            CHECK (ended_at IS NULL OR ended_at >= started_at)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_assignments');
    }
};
