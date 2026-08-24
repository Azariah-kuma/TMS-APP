<?php

declare(strict_types=1);

use App\Actions\Employees\BulkImportEmployeesAction;
use App\Enums\EmployeeRole;
use App\Exceptions\InvalidCsvFileException;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

it('CSVサンプルファイル（tests/Fixtures/employees_import_sample.csv）を全行取り込める', function () {
    Notification::fake();

    Department::create(['name' => '開発部', 'code' => 'DEV']);
    Department::create(['name' => '営業部', 'code' => 'SALES']);
    Department::create(['name' => '人事部', 'code' => 'HR']);
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);
    Position::create(['name' => '主任', 'code' => 'LEADER', 'rank' => 2]);
    $manager = Employee::factory()->create(['employee_code' => 'EMP-0001']);

    $file = new UploadedFile(
        base_path('tests/Fixtures/employees_import_sample.csv'),
        'employees_import_sample.csv',
        'text/csv',
        null,
        true,
    );

    $result = app(BulkImportEmployeesAction::class)->execute($file);

    expect($result['errors'])->toBe([])
        ->and($result['created'])->toHaveCount(3);

    expect($result['created'][0]->employee_code)->toBe('EMP-1001')
        ->and($result['created'][0]->currentAssignment->manager_id)->toBe($manager->id)
        ->and($result['created'][2]->role)->toBe(EmployeeRole::Hr);
});

it('部署コード・役職コードで部署・役職を解決して登録する', function () {
    Notification::fake();

    Department::create(['name' => '開発部', 'code' => 'DEV']);
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);

    $csv = "姓,名,セイ,メイ,メールアドレス,従業員コード,ロール,入社日,部署コード,役職コード,上司の従業員コード\n"
        ."山田,太郎,ヤマダ,タロウ,taro@example.com,EMP-2001,一般社員,2026-04-01,DEV,STAFF,\n";

    $result = app(BulkImportEmployeesAction::class)->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv));

    expect($result['errors'])->toBe([])
        ->and($result['created'])->toHaveCount(1);

    $employee = $result['created'][0]->fresh(['currentAssignment']);
    expect($employee->currentAssignment->department->code)->toBe('DEV')
        ->and($employee->currentAssignment->position->code)->toBe('STAFF');
});

it('1行のバリデーションエラーはその行だけをエラーにし、他の行の登録は継続する', function () {
    Notification::fake();

    Department::create(['name' => '開発部', 'code' => 'DEV']);
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);

    $csv = "姓,名,セイ,メイ,メールアドレス,従業員コード,ロール,入社日,部署コード,役職コード,上司の従業員コード\n"
        ."不正,太郎,ヤマダ,タロウ,invalid-email,EMP-3001,一般社員,2026-04-01,DEV,STAFF,\n"
        ."山田,花子,ヤマダ,ハナコ,hanako@example.com,EMP-3002,一般社員,2026-04-01,DEV,STAFF,\n";

    $result = app(BulkImportEmployeesAction::class)->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv));

    expect($result['created'])->toHaveCount(1)
        ->and($result['created'][0]->employee_code)->toBe('EMP-3002')
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['row'])->toBe(2);
});

it('存在しない部署コードの行はエラーになる', function () {
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);

    $csv = "姓,名,セイ,メイ,メールアドレス,従業員コード,ロール,入社日,部署コード,役職コード,上司の従業員コード\n"
        ."山田,太郎,ヤマダ,タロウ,taro@example.com,EMP-4001,一般社員,2026-04-01,NOPE,STAFF,\n";

    $result = app(BulkImportEmployeesAction::class)->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv));

    expect($result['created'])->toBe([])
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['message'])->toContain('NOPE');

    // 失敗した行だけを抽出したCSVを再構成できるよう、元のCSVヘッダー名で行データを持つ。
    expect($result['errors'][0]['data'])->toBe([
        '姓' => '山田',
        '名' => '太郎',
        'セイ' => 'ヤマダ',
        'メイ' => 'タロウ',
        'メールアドレス' => 'taro@example.com',
        '従業員コード' => 'EMP-4001',
        'ロール' => '一般社員',
        '入社日' => '2026-04-01',
        '部署コード' => 'NOPE',
        '役職コード' => 'STAFF',
        '上司の従業員コード' => '',
    ]);
});

it('CSV内でメールアドレスが重複している行はエラーになる（先の行は登録される）', function () {
    Notification::fake();

    Department::create(['name' => '開発部', 'code' => 'DEV']);
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);

    $csv = "姓,名,セイ,メイ,メールアドレス,従業員コード,ロール,入社日,部署コード,役職コード,上司の従業員コード\n"
        ."山田,太郎,ヤマダ,タロウ,dup@example.com,EMP-5001,一般社員,2026-04-01,DEV,STAFF,\n"
        ."鈴木,花子,スズキ,ハナコ,dup@example.com,EMP-5002,一般社員,2026-04-01,DEV,STAFF,\n";

    $result = app(BulkImportEmployeesAction::class)->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv));

    expect($result['created'])->toHaveCount(1)
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['row'])->toBe(3);

    expect(User::where('email', 'dup@example.com')->count())->toBe(1);
});

it('本物のデータベース競合状態が発生した行だけをエラーにし、他の行の登録は継続する', function () {
    Notification::fake();

    Department::create(['name' => '開発部', 'code' => 'DEV']);
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);

    // バリデーションのunique判定を通過した直後に、別プロセスが同じメールアドレスで
    // 先に登録を完了させてしまった状況を単一コネクション内で再現する。
    User::creating(function (User $user): void {
        if ($user->email === 'race@example.com') {
            DB::table('users')->insert([
                'last_name' => '衝突',
                'first_name' => '太郎',
                'last_name_kana' => 'ショウトツ',
                'first_name_kana' => 'タロウ',
                'email' => 'race@example.com',
                'password' => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    });

    $csv = "姓,名,セイ,メイ,メールアドレス,従業員コード,ロール,入社日,部署コード,役職コード,上司の従業員コード\n"
        ."山田,太郎,ヤマダ,タロウ,race@example.com,EMP-6001,一般社員,2026-04-01,DEV,STAFF,\n"
        ."鈴木,花子,スズキ,ハナコ,hanako6@example.com,EMP-6002,一般社員,2026-04-01,DEV,STAFF,\n";

    try {
        $result = app(BulkImportEmployeesAction::class)
            ->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv));
    } finally {
        User::flushEventListeners();
    }

    expect($result['created'])->toHaveCount(1)
        ->and($result['created'][0]->employee_code)->toBe('EMP-6002')
        ->and($result['errors'])->toHaveCount(1)
        ->and($result['errors'][0]['row'])->toBe(2);
});

it('CSV末尾の空行は無視される', function () {
    Notification::fake();

    Department::create(['name' => '開発部', 'code' => 'DEV']);
    Position::create(['name' => '一般', 'code' => 'STAFF', 'rank' => 1]);

    $csv = "姓,名,セイ,メイ,メールアドレス,従業員コード,ロール,入社日,部署コード,役職コード,上司の従業員コード\n"
        ."山田,太郎,ヤマダ,タロウ,taro7@example.com,EMP-7001,一般社員,2026-04-01,DEV,STAFF,\n"
        ."\n";

    $result = app(BulkImportEmployeesAction::class)
        ->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv));

    expect($result['errors'])->toBe([])
        ->and($result['created'])->toHaveCount(1);
});

it('必須ヘッダーが不足しているCSVは拒否される', function () {
    $csv = "姓,名,メールアドレス\n山田,太郎,taro@example.com\n";

    expect(fn () => app(BulkImportEmployeesAction::class)->execute(UploadedFile::fake()->createWithContent('employees.csv', $csv)))
        ->toThrow(InvalidCsvFileException::class);
});

it('空のCSVファイルは拒否される', function () {
    expect(fn () => app(BulkImportEmployeesAction::class)->execute(UploadedFile::fake()->createWithContent('employees.csv', '')))
        ->toThrow(InvalidCsvFileException::class);
});
