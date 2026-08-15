import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Department } from '../../../core/models/department';
import { Position } from '../../../core/models/position';
import { Employee } from '../../../core/models/employee';

@Component({
  selector: 'app-employee-onboard',
  imports: [ReactiveFormsModule],
  templateUrl: './employee-onboard.html',
})
export class EmployeeOnboard implements OnInit {
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
    name: ['', Validators.required],
    email: ['', [Validators.required, Validators.email]],
    password: ['', Validators.required],
    password_confirmation: ['', Validators.required],
    employee_code: ['', Validators.required],
    role: ['employee', Validators.required],
    hired_at: ['', Validators.required],
    department_id: [0, Validators.required],
    position_id: [0, Validators.required],
    manager_id: [''],
  });

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
        manager_id: raw.manager_id ? Number(raw.manager_id) : null,
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
