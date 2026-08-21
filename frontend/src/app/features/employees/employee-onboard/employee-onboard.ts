import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Department } from '../../../core/models/department';
import { Position } from '../../../core/models/position';
import { Employee } from '../../../core/models/employee';
import { toKatakana } from '../../../core/utils/kana';
import { isFieldInvalid, toId } from '../../../core/utils/forms';

@Component({
  selector: 'app-employee-onboard',
  imports: [ReactiveFormsModule],
  templateUrl: './employee-onboard.html',
})
export class EmployeeOnboard implements OnInit {
  private static readonly KANA_PATTERN = /^[゠-ヿ]+$/;

  private readonly fb = inject(FormBuilder);
  private readonly employeeService = inject(EmployeeService);
  private readonly masterData = inject(MasterDataService);
  private readonly router = inject(Router);

  readonly departments = signal<Department[]>([]);
  readonly positions = signal<Position[]>([]);
  readonly employees = signal<Employee[]>([]);
  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    last_name: ['', Validators.required],
    first_name: ['', Validators.required],
    last_name_kana: ['', [Validators.required, Validators.pattern(EmployeeOnboard.KANA_PATTERN)]],
    first_name_kana: ['', [Validators.required, Validators.pattern(EmployeeOnboard.KANA_PATTERN)]],
    email: ['', [Validators.required, Validators.email]],
    employee_code: ['', Validators.required],
    role: ['employee', Validators.required],
    hired_at: ['', Validators.required],
    department_id: [0, Validators.required],
    position_id: [0, Validators.required],
    manager_id: [''],
  });

  fieldInvalid(name: keyof typeof this.form.controls): boolean {
    return isFieldInvalid(this.form, name);
  }

  /**
   * フリガナ欄の入力をカタカナに自動変換する。IME変換確定前（isComposing中）は
   * 変換候補の表示が崩れるため何もせず、確定後（inputイベントの非composing時、または
   * compositionendイベント）にのみ変換する。
   */
  convertToKatakana(event: Event, controlName: 'last_name_kana' | 'first_name_kana'): void {
    if ((event as InputEvent).isComposing) {
      return;
    }

    const input = event.target as HTMLInputElement;
    const converted = toKatakana(input.value);

    if (converted !== input.value) {
      this.form.controls[controlName].setValue(converted);
    }
  }

  ngOnInit(): void {
    this.masterData.departments().subscribe((departments) => this.departments.set(departments));
    this.masterData.positions().subscribe((positions) => this.positions.set(positions));
    this.employeeService.list().subscribe((employees) => this.employees.set(employees));
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.error.set(null);

    const raw = this.form.getRawValue();

    this.employeeService
      .onboard({
        ...raw,
        role: raw.role as 'employee' | 'hr',
        department_id: toId(raw.department_id),
        position_id: toId(raw.position_id),
        manager_id: raw.manager_id ? toId(raw.manager_id) : null,
      })
      .subscribe({
        next: (employee) => this.router.navigate(['/employees', employee.id]),
        error: (err) => {
          this.submitting.set(false);
          this.error.set(err.error?.message ?? '登録に失敗しました。入力内容を確認してください。');
        },
      });
  }
}
