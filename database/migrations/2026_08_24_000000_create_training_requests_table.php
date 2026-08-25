<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 従業員が研修受講を自ら申請し、上司またはHRが承認/却下する記録。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->date('due_at')->nullable();
            $table->foreignId('decided_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_comment')->nullable();
            $table->foreignId('training_enrollment_id')->nullable()->constrained('training_enrollments')->nullOnDelete();
            $table->timestamps();

            $table->index(['employee_id', 'status']);
            $table->index(['training_id', 'status']);
        });

        // 同一従業員・同一研修について、承認待ち(pending)の申請は同時に1件までとする。
        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX training_requests_one_pending_per_employee_training
            ON training_requests (employee_id, training_id)
            WHERE status = 'pending'
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('training_requests');
    }
};
