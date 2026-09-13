import { Component, inject, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { BulkImportResult, EmployeeService } from '../../../core/services/employee.service';
import { downloadCsv, rowsToCsv } from '../../../core/utils/csv';

@Component({
  selector: 'app-employee-bulk-import',
  imports: [RouterLink],
  templateUrl: './employee-bulk-import.html',
})
export class EmployeeBulkImport {
  private readonly employeeService = inject(EmployeeService);

  readonly file = signal<File | null>(null);
  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);
  readonly result = signal<BulkImportResult | null>(null);

  onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.file.set(input.files?.[0] ?? null);
    this.result.set(null);
    this.error.set(null);
  }

  submit(): void {
    const file = this.file();
    if (!file) {
      return;
    }

    this.submitting.set(true);
    this.error.set(null);
    this.result.set(null);

    this.employeeService.bulkImport(file).subscribe({
      next: (result) => {
        this.submitting.set(false);
        this.result.set(result);
      },
      error: (err) => {
        this.submitting.set(false);
        this.error.set(err.error?.message ?? 'CSVの取り込みに失敗しました。');
      },
    });
  }

  /** 記入例入りのサンプルCSVをダウンロードする。部署コード・役職コードは実際の環境のものに置き換える必要がある。 */
  downloadSample(): void {
    const headers = [
      '姓',
      '名',
      'セイ',
      'メイ',
      'メールアドレス',
      '従業員コード',
      'ロール',
      '入社日',
      '部署コード',
      '役職コード',
      '上司の従業員コード',
    ];
    const rows = [
      {
        姓: '山田',
        名: '太郎',
        セイ: 'ヤマダ',
        メイ: 'タロウ',
        メールアドレス: 'taro.yamada@example.com',
        従業員コード: 'EMP-1001',
        ロール: '一般社員',
        入社日: '2026-04-01',
        部署コード: 'DEPT-DEV',
        役職コード: 'POS-STAFF',
        上司の従業員コード: '',
      },
      {
        姓: '鈴木',
        名: '花子',
        セイ: 'スズキ',
        メイ: 'ハナコ',
        メールアドレス: 'hanako.suzuki@example.com',
        従業員コード: 'EMP-1002',
        ロール: '人事',
        入社日: '2026-04-01',
        部署コード: 'DEPT-HR',
        役職コード: 'POS-STAFF',
        上司の従業員コード: '',
      },
    ];

    downloadCsv('employees_import_sample.csv', rowsToCsv(headers, rows));
  }

  /** 失敗した行だけを、元と同じ形式のCSVとしてダウンロードする。修正後にそのまま再アップロードできる。 */
  downloadFailedRows(): void {
    const errors = this.result()?.errors ?? [];
    if (errors.length === 0) {
      return;
    }

    const headers = Object.keys(errors[0].data);
    const csv = rowsToCsv(
      headers,
      errors.map((e) => e.data),
    );

    downloadCsv('employees_import_errors.csv', csv);
  }
}
