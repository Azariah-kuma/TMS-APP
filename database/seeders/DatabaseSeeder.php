<?php

namespace Database\Seeders;

use App\Enums\EmployeeRole;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * 他メンバーがローカル/CIで動かしたときに、ログイン直後からHR画面や
     * CSV一括登録のサンプルを試せるよう、部署・役職マスタと初期HRアカウントを用意する。
     * （tests/Fixtures/employees_import_sample.csv）
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $departments = collect([
            ['name' => '開発部', 'code' => 'DEV'],
            ['name' => '営業部', 'code' => 'SALES'],
            ['name' => '人事部', 'code' => 'HR'],
        ])->map(fn (array $attributes) => Department::create($attributes));

        $positions = collect([
            ['name' => '一般', 'code' => 'STAFF', 'rank' => 1],
            ['name' => '主任', 'code' => 'LEADER', 'rank' => 2],
            ['name' => '課長', 'code' => 'MANAGER', 'rank' => 3],
        ])->map(fn (array $attributes) => Position::create($attributes));

        $user = User::factory()->create([
            'last_name' => 'テスト',
            'first_name' => 'ユーザー',
            'last_name_kana' => 'テスト',
            'first_name_kana' => 'ユーザー',
            'email' => 'test@example.com',
        ]);

        $employee = Employee::create([
            'user_id' => $user->id,
            'employee_code' => 'EMP-0001',
            'role' => EmployeeRole::Hr,
            'hired_at' => now()->subYear(),
        ]);

        EmployeeAssignment::create([
            'employee_id' => $employee->id,
            'department_id' => $departments->firstWhere('code', 'HR')->id,
            'position_id' => $positions->firstWhere('code', 'MANAGER')->id,
            'manager_id' => null,
            'started_at' => now()->subYear(),
        ]);
    }
}
