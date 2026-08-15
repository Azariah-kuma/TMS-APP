<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('employee_code')->unique();
            // employee: 一般社員（自分の研修のみ閲覧可）／ hr: 人事（全権限）。
            // 「上司」はロールではなく employee_assignments.manager_id から導出される関係。
            $table->string('role')->default('employee');
            $table->date('hired_at');
            $table->date('retired_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
