<?php

declare(strict_types=1);

namespace App\Actions\Reports;

use App\Enums\TrainingEnrollmentStatus;
use App\Models\TrainingEnrollment;

final class BuildTrainingEnrollmentsCsvAction
{
    private const HEADERS = ['従業員コード', '氏名', '部署', '役職', '研修', 'ステータス', '進捗', '期限', '完了日時'];

    /** Excel等でCSVを開いたときに数式として実行されてしまう先頭文字（CSVインジェクション対策）。 */
    private const FORMULA_PREFIXES = ['=', '+', '-', '@'];

    /** 全受講記録をCSV文字列として出力する。 */
    public function execute(): string
    {
        $enrollments = TrainingEnrollment::query()
            ->with(['employee.user', 'employee.currentAssignment.department', 'employee.currentAssignment.position', 'training'])
            ->get();

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, self::HEADERS);

        foreach ($enrollments as $enrollment) {
            fputcsv($handle, [
                $this->escapeFormula($enrollment->employee->employee_code),
                $this->escapeFormula($enrollment->employee->user->name),
                $this->escapeFormula($enrollment->employee->currentAssignment?->department?->name ?? ''),
                $this->escapeFormula($enrollment->employee->currentAssignment?->position?->name ?? ''),
                $this->escapeFormula($enrollment->training->title),
                match ($enrollment->status) {
                    TrainingEnrollmentStatus::NotStarted => '未着手',
                    TrainingEnrollmentStatus::InProgress => '受講中',
                    TrainingEnrollmentStatus::Completed => '完了',
                },
                $enrollment->progress,
                $enrollment->due_at?->toDateString() ?? '',
                $enrollment->completed_at?->toDateString() ?? '',
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * 部署名・研修タイトル等はHRが自由入力できるため、先頭が =+-@ の値をExcel等で開くと
     * 数式として実行されてしまう（CSVインジェクション）。先頭にシングルクォートを付けて
     * 文字列として扱わせることで無害化する。
     */
    private function escapeFormula(string $value): string
    {
        if ($value !== '' && in_array($value[0], self::FORMULA_PREFIXES, true)) {
            return "'{$value}";
        }

        return $value;
    }
}
