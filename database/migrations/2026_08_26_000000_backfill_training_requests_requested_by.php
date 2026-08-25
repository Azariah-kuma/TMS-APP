<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * requested_by_employee_id 列を追加する前に作られた申請は、「代理申請」扱いになってしまう。
 * そのため、employee_id をそのままコピーして補完する。
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('training_requests')
            ->whereNull('requested_by_employee_id')
            ->update(['requested_by_employee_id' => DB::raw('employee_id')]);
    }

    public function down(): void
    {
        // 補完のみのため、ロールバックでは何もしない。
    }
};
