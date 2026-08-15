<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 上司権限の一時的な委任（課長代理・兼務カバーなど）。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('delegate_id')->constrained('employees')->cascadeOnDelete();
            $table->date('started_at');
            $table->date('ended_at')->nullable();
            $table->timestamps();

            $table->index(['delegate_id', 'ended_at']);
            $table->index(['delegator_id', 'ended_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE delegations
            ADD CONSTRAINT delegations_delegator_delegate_differ
            CHECK (delegator_id <> delegate_id)
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE delegations
            ADD CONSTRAINT delegations_period_valid
            CHECK (ended_at IS NULL OR ended_at >= started_at)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('delegations');
    }
};
