import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Department } from '../../../core/models/department';

@Component({
  selector: 'app-department-list',
  imports: [ReactiveFormsModule],
  templateUrl: './department-list.html',
})
export class DepartmentList implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly masterData = inject(MasterDataService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly departments = signal<Department[]>([]);
  readonly loading = signal(true);
  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    code: ['', Validators.required],
  });

  /** 「操作」を開いている行（誤操作で削除しないよう、通常は畳んでおく）。 */
  readonly openActionsId = signal<number | null>(null);
  /** 編集フォームを表示中の行。 */
  readonly editingId = signal<number | null>(null);
  readonly editSubmitting = signal(false);
  readonly editError = signal<string | null>(null);
  readonly editForm = this.fb.nonNullable.group({
    name: ['', Validators.required],
    code: ['', Validators.required],
  });

  readonly deletingId = signal<number | null>(null);
  readonly deleteError = signal<string | null>(null);

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.masterData.departments().subscribe((departments) => {
      this.departments.set(departments);
      this.loading.set(false);
    });
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.error.set(null);

    this.masterData.createDepartment(this.form.getRawValue()).subscribe({
      next: (department) => {
        this.submitting.set(false);
        this.departments.update((list) => [...list, department].sort((a, b) => a.name.localeCompare(b.name)));
        this.form.reset({ name: '', code: '' });
      },
      error: (err) => {
        this.submitting.set(false);
        this.error.set(err.error?.message ?? '部署の追加に失敗しました。入力内容を確認してください。');
      },
    });
  }

  toggleActions(id: number): void {
    this.editingId.set(null);
    this.deleteError.set(null);
    this.openActionsId.set(this.openActionsId() === id ? null : id);
  }

  startEdit(department: Department): void {
    this.editError.set(null);
    this.editForm.reset({ name: department.name, code: department.code });
    this.editingId.set(department.id);
  }

  cancelEdit(): void {
    this.editingId.set(null);
  }

  submitEdit(id: number): void {
    if (this.editForm.invalid) {
      return;
    }

    this.editSubmitting.set(true);
    this.editError.set(null);

    this.masterData.updateDepartment(id, this.editForm.getRawValue()).subscribe({
      next: () => {
        this.editSubmitting.set(false);
        this.editingId.set(null);
        this.openActionsId.set(null);
        this.load();
      },
      error: (err) => {
        this.editSubmitting.set(false);
        this.editError.set(err.error?.message ?? '部署の更新に失敗しました。入力内容を確認してください。');
      },
    });
  }

  deleteDepartment(department: Department): void {
    if (!confirm(`「${department.name}」を削除しますか？この操作は取り消せません。`)) {
      return;
    }

    this.deletingId.set(department.id);
    this.deleteError.set(null);

    this.masterData.deleteDepartment(department.id).subscribe({
      next: () => {
        this.deletingId.set(null);
        this.openActionsId.set(null);
        this.load();
      },
      error: (err) => {
        this.deletingId.set(null);
        this.deleteError.set(err.error?.message ?? '部署の削除に失敗しました。');
      },
    });
  }
}
