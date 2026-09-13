<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 部署ごとの年度（4/1〜翌3/31）研修予算。
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->decimal('budget_amount', 12, 2);
            $table->timestamps();

            $table->unique(['department_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_budgets');
    }
};
