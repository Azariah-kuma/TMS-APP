<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Enums\EmployeeRole;
use App\Exceptions\InvalidCsvFileException;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

final class BulkImportEmployeesAction
{
    /** CSVのヘッダー名 => バリデーション・登録処理内で使う内部キー名。 */
    private const HEADERS = [
        '姓' => 'last_name',
        '名' => 'first_name',
        'セイ' => 'last_name_kana',
        'メイ' => 'first_name_kana',
        'メールアドレス' => 'email',
        '従業員コード' => 'employee_code',
        'ロール' => 'role',
        '入社日' => 'hired_at',
        '部署コード' => 'department_code',
        '役職コード' => 'position_code',
        '上司の従業員コード' => 'manager_employee_code',
    ];

    private const ROLE_LABELS = [
        '一般社員' => EmployeeRole::Employee,
        '人事' => EmployeeRole::Hr,
    ];

    public function __construct(
        private readonly OnboardEmployeeAction $onboardEmployeeAction,
    ) {}

    /**
     * CSVファイルから複数の新入社員をまとめて登録する。
     * 1行の失敗（バリデーションエラー・メールアドレス重複等）でバッチ全体を失敗させず、
     * その行だけをエラーとして記録し、他の行の登録は継続する。
     *
     * @return array{created: list<Employee>, errors: list<array{row: int, message: string, data: array<string, string>}>}
     */
    public function execute(UploadedFile $file): array
    {
        $rows = $this->readRows($file);

        $created = [];
        $errors = [];

        foreach ($rows as $rowNumber => $row) {
            try {
                $created[] = $this->importRow($row);
            } catch (ValidationException $e) {
                $errors[] = $this->errorFor($rowNumber, $row, (string) collect($e->errors())->flatten()->first());
            } catch (QueryException) {
                $errors[] = $this->errorFor($rowNumber, $row, 'メールアドレスまたは従業員コードが重複しています。');
            }
        }

        return ['created' => $created, 'errors' => $errors];
    }

    /**
     * @param  array<string, string>  $row
     * @return array{row: int, message: string, data: array<string, string>}
     */
    private function errorFor(int $rowNumber, array $row, string $message): array
    {
        // 元のCSVヘッダー名で行データを返し、フロントで「失敗した行だけのCSV」を
        // 再構成して、修正後にそのまま再アップロードできるようにする。
        return [
            'row' => $rowNumber,
            'message' => $message,
            'data' => array_combine(array_keys(self::HEADERS), array_values($row)),
        ];
    }

    /** @param array<string, string> $row */
    private function importRow(array $row): Employee
    {
        $departmentId = Department::query()->where('code', $row['department_code'])->value('id');
        $positionId = Position::query()->where('code', $row['position_code'])->value('id');
        $managerId = $row['manager_employee_code'] !== ''
            ? Employee::query()->where('employee_code', $row['manager_employee_code'])->value('id')
            : null;
        $role = self::ROLE_LABELS[$row['role']] ?? null;

        $data = Validator::make([
            'last_name' => $row['last_name'],
            'first_name' => $row['first_name'],
            'last_name_kana' => $row['last_name_kana'],
            'first_name_kana' => $row['first_name_kana'],
            'email' => $row['email'],
            'employee_code' => $row['employee_code'],
            'role' => $role?->value,
            'hired_at' => $row['hired_at'],
            'department_id' => $departmentId,
            'position_id' => $positionId,
            'manager_id' => $managerId,
        ], (new StoreEmployeeRequest)->rules(), array_merge((new StoreEmployeeRequest)->messages(), [
            'role.required' => 'ロールは「一般社員」または「人事」を指定してください。',
            'department_id.required' => "部署コード「{$row['department_code']}」が見つかりません。",
            'position_id.required' => "役職コード「{$row['position_code']}」が見つかりません。",
            'manager_id.integer' => "上司の従業員コード「{$row['manager_employee_code']}」が見つかりません。",
        ]))->validate();

        return $this->onboardEmployeeAction->execute(
            lastName: $data['last_name'],
            firstName: $data['first_name'],
            lastNameKana: $data['last_name_kana'],
            firstNameKana: $data['first_name_kana'],
            email: $data['email'],
            employeeCode: $data['employee_code'],
            role: $role,
            hiredAt: Carbon::parse($data['hired_at']),
            departmentId: $data['department_id'],
            positionId: $data['position_id'],
            managerId: $data['manager_id'] ?? null,
        );
    }

    /** @return array<int, array<string, string>> 行番号（ヘッダー行を1とするCSV上の行番号）=> 内部キーに変換済みの行データ */
    private function readRows(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw new InvalidCsvFileException('CSVファイルが空です。');
            }

            // Excelで保存したCSVの先頭に付与されるUTF-8 BOMを除去する。
            $header[0] = preg_replace('/^\x{FEFF}/u', '', (string) $header[0]);

            $missing = array_diff(array_keys(self::HEADERS), $header);
            if ($missing !== []) {
                throw new InvalidCsvFileException(
                    'CSVのヘッダーが不正です。不足している列: '.implode('、', $missing),
                );
            }

            $rows = [];
            $rowNumber = 1;

            while (($line = fgetcsv($handle)) !== false) {
                $rowNumber++;

                // 末尾の空行はスキップする（Excel等が末尾に空行を残すことがあるため）。
                if ($line === [null] || $line === ['']) {
                    continue;
                }

                $byHeader = array_combine($header, array_pad($line, count($header), ''));

                $rows[$rowNumber] = collect(self::HEADERS)
                    ->mapWithKeys(fn (string $key, string $csvHeader) => [$key => trim((string) ($byHeader[$csvHeader] ?? ''))])
                    ->all();
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }
}
