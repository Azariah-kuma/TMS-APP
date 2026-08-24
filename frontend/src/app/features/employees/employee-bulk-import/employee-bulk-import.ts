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
