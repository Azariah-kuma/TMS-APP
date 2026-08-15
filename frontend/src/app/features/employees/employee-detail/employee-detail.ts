import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Employee } from '../../../core/models/employee';
import { EmployeeAssignment } from '../../../core/models/employee-assignment';
import { Delegation } from '../../../core/models/delegation';
import { Department } from '../../../core/models/department';
import { Position } from '../../../core/models/position';

@Component({
  selector: 'app-employee-detail',
  imports: [ReactiveFormsModule],
  templateUrl: './employee-detail.html',
})
export class EmployeeDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  private readonly employeeService = inject(EmployeeService);
  private readonly masterData = inject(MasterDataService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly employee = signal<Employee | null>(null);
  readonly assignments = signal<EmployeeAssignment[]>([]);
  readonly delegations = signal<Delegation[]>([]);
  readonly departments = signal<Department[]>([]);
  readonly positions = signal<Position[]>([]);
  readonly colleagues = signal<Employee[]>([]);
  readonly loading = signal(true);

  readonly showTransferForm = signal(false);
  readonly transferSubmitting = signal(false);
  readonly transferError = signal<string | null>(null);
  readonly transferForm = this.fb.nonNullable.group({
    department_id: [0, Validators.required],
    position_id: [0, Validators.required],
    manager_id: [''],
    started_at: ['', Validators.required],
  });

  readonly showDelegationForm = signal(false);
  readonly delegationSubmitting = signal(false);
  readonly delegationError = signal<string | null>(null);
  readonly delegationForm = this.fb.nonNullable.group({
    delegate_id: [0, Validators.required],
    started_at: ['', Validators.required],
    ended_at: [''],
  });

  private employeeId = 0;

  ngOnInit(): void {
    this.employeeId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadEmployee();
    this.loadAssignments();

    if (this.isHr()) {
      this.employeeService.delegationsGiven(this.employeeId).subscribe((d) => this.delegations.set(d));
      this.masterData.departments().subscribe((d) => this.departments.set(d));
      this.masterData.positions().subscribe((p) => this.positions.set(p));
      this.employeeService.list().subscribe((employees) => this.colleagues.set(employees));
    }
  }

  private loadEmployee(): void {
    this.employeeService.get(this.employeeId).subscribe((employee) => {
      this.employee.set(employee);
      this.loading.set(false);
    });
  }

  private loadAssignments(): void {
    this.employeeService.assignments(this.employeeId).subscribe((a) => this.assignments.set(a));
  }

  submitTransfer(): void {
    if (this.transferForm.invalid) {
      return;
    }

    this.transferSubmitting.set(true);
    this.transferError.set(null);

    const raw = this.transferForm.getRawValue();

    this.employeeService
      .transfer(this.employeeId, {
        department_id: raw.department_id,
        position_id: raw.position_id,
        manager_id: raw.manager_id ? Number(raw.manager_id) : null,
        started_at: raw.started_at,
      })
      .subscribe({
        next: () => {
          this.transferSubmitting.set(false);
          this.showTransferForm.set(false);
          this.loadEmployee();
          this.loadAssignments();
        },
        error: (err) => {
          this.transferSubmitting.set(false);
          this.transferError.set(err.error?.message ?? '異動の登録に失敗しました。');
        },
      });
  }

  submitDelegation(): void {
    if (this.delegationForm.invalid) {
      return;
    }

    this.delegationSubmitting.set(true);
    this.delegationError.set(null);

    const raw = this.delegationForm.getRawValue();

    this.employeeService
      .createDelegation(this.employeeId, {
        delegate_id: raw.delegate_id,
        started_at: raw.started_at,
        ended_at: raw.ended_at || null,
      })
      .subscribe({
        next: (delegation) => {
          this.delegationSubmitting.set(false);
          this.showDelegationForm.set(false);
          this.delegations.update((list) => [delegation, ...list]);
        },
        error: (err) => {
          this.delegationSubmitting.set(false);
          this.delegationError.set(err.error?.message ?? '委任の登録に失敗しました。');
        },
      });
  }

  revokeDelegation(delegationId: number): void {
    this.employeeService.revokeDelegation(delegationId).subscribe((updated) => {
      this.delegations.update((list) => list.map((d) => (d.id === updated.id ? updated : d)));
    });
  }
}
