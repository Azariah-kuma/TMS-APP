<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 多段階承認における、各段階の決裁記録（誰が・いつ・承認/却下したか）を保持する。
 * 1段階分の決裁が確定するたびに1行追加される（保留中の段階には行を作らない）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_request_approval_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_request_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stage_number');
            $table->foreignId('decided_by_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('decided_at');
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['training_request_id', 'stage_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_request_approval_stages');
    }
};
