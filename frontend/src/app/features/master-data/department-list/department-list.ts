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
}
