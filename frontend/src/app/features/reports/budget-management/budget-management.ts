import { Component, OnInit, inject, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { DepartmentBudgetService } from '../../../core/services/department-budget.service';
import { ReportService } from '../../../core/services/report.service';
import { BudgetUsageRow } from '../../../core/models/department-budget';
import { currentFiscalYear } from '../../../core/utils/fiscal-year';

@Component({
  selector: 'app-budget-management',
  imports: [ReactiveFormsModule],
  templateUrl: './budget-management.html',
})
export class BudgetManagement implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly departmentBudgets = inject(DepartmentBudgetService);
  private readonly reportService = inject(ReportService);

  readonly fiscalYear = signal(currentFiscalYear());
  readonly rows = signal<BudgetUsageRow[]>([]);
  readonly loading = signal(true);

  /** 予算入力フォームを開いている部署。 */
  readonly editingDepartmentId = signal<number | null>(null);
  readonly editSubmitting = signal(false);
  readonly editError = signal<string | null>(null);
  readonly editForm = this.fb.nonNullable.group({
    budget_amount: [0, [Validators.required, Validators.min(0)]],
  });

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);

    this.reportService.budgetUsage(this.fiscalYear()).subscribe((rows) => {
      this.rows.set(rows);
      this.loading.set(false);
    });
  }

  changeFiscalYear(year: number): void {
    this.fiscalYear.set(year);
    this.editingDepartmentId.set(null);
    this.load();
  }

  startEdit(row: BudgetUsageRow): void {
    this.editError.set(null);
    this.editForm.reset({ budget_amount: row.budget_amount ?? 0 });
    this.editingDepartmentId.set(row.department_id);
  }

  cancelEdit(): void {
    this.editingDepartmentId.set(null);
  }

  submitEdit(row: BudgetUsageRow): void {
    if (this.editForm.invalid) {
      return;
    }

    this.editSubmitting.set(true);
    this.editError.set(null);

    const budgetAmount = this.editForm.getRawValue().budget_amount;
    const request = row.budget_id
      ? this.departmentBudgets.update(row.budget_id, { budget_amount: budgetAmount })
      : this.departmentBudgets.create({
          department_id: row.department_id,
          fiscal_year: this.fiscalYear(),
          budget_amount: budgetAmount,
        });

    request.subscribe({
      next: () => {
        this.editSubmitting.set(false);
        this.editingDepartmentId.set(null);
        this.load();
      },
      error: (err) => {
        this.editSubmitting.set(false);
        this.editError.set(err.error?.message ?? '予算の保存に失敗しました。');
      },
    });
  }
}
