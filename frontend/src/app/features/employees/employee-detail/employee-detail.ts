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
import { toId } from '../../../core/utils/forms';
import { toKatakana } from '../../../core/utils/kana';

@Component({
  selector: 'app-employee-detail',
  imports: [ReactiveFormsModule],
  templateUrl: './employee-detail.html',
})
export class EmployeeDetail implements OnInit {
  private static readonly KANA_PATTERN = /^[゠-ヿ]+$/;

  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  private readonly employeeService = inject(EmployeeService);
  private readonly masterData = inject(MasterDataService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly employee = signal<Employee | null>(null);

  /** 氏名訂正・退職登録は誤操作を防ぐため、既定で畳んでおく。 */
  readonly showDangerZone = signal(false);

  readonly editingName = signal(false);
  readonly nameEditSubmitting = signal(false);
  readonly nameEditError = signal<string | null>(null);
  readonly nameForm = this.fb.nonNullable.group({
    last_name: ['', Validators.required],
    first_name: ['', Validators.required],
    last_name_kana: ['', [Validators.required, Validators.pattern(EmployeeDetail.KANA_PATTERN)]],
    first_name_kana: ['', [Validators.required, Validators.pattern(EmployeeDetail.KANA_PATTERN)]],
  });
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

  readonly resendingInvite = signal(false);
  readonly resendInviteMessage = signal<string | null>(null);
  readonly resendInviteError = signal<string | null>(null);

  readonly showRetireForm = signal(false);
  readonly retireSubmitting = signal(false);
  readonly retireError = signal<string | null>(null);
  readonly retireForm = this.fb.nonNullable.group({
    retired_at: ['', Validators.required],
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

  /** 婚姻等による姓の変更など、氏名・フリガナの訂正フォームを開く。 */
  startEditName(): void {
    const employee = this.employee();
    if (!employee) {
      return;
    }

    this.nameEditError.set(null);
    this.nameForm.reset({
      last_name: employee.last_name,
      first_name: employee.first_name,
      last_name_kana: employee.last_name_kana,
      first_name_kana: employee.first_name_kana,
    });
    this.editingName.set(true);
  }

  cancelEditName(): void {
    this.editingName.set(false);
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
      this.nameForm.controls[controlName].setValue(converted);
    }
  }

  submitEditName(): void {
    if (this.nameForm.invalid) {
      return;
    }

    if (!confirm('氏名・フリガナを訂正します。よろしいですか？')) {
      return;
    }

    this.nameEditSubmitting.set(true);
    this.nameEditError.set(null);

    this.employeeService.updateName(this.employeeId, this.nameForm.getRawValue()).subscribe({
      next: (updated) => {
        this.nameEditSubmitting.set(false);
        this.editingName.set(false);
        this.employee.set(updated);
      },
      error: (err) => {
        this.nameEditSubmitting.set(false);
        this.nameEditError.set(err.error?.message ?? '氏名の訂正に失敗しました。');
      },
    });
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
        department_id: toId(raw.department_id),
        position_id: toId(raw.position_id),
        manager_id: raw.manager_id ? toId(raw.manager_id) : null,
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

  submitRetire(): void {
    if (this.retireForm.invalid) {
      return;
    }

    if (!confirm('退職登録します。現在の配属も終了します。よろしいですか？')) {
      return;
    }

    this.retireSubmitting.set(true);
    this.retireError.set(null);

    this.employeeService.retire(this.employeeId, this.retireForm.getRawValue().retired_at).subscribe({
      next: (updated) => {
        this.retireSubmitting.set(false);
        this.showRetireForm.set(false);
        this.employee.set(updated);
        this.loadAssignments();
      },
      error: (err) => {
        this.retireSubmitting.set(false);
        this.retireError.set(err.error?.message ?? '退職登録に失敗しました。');
      },
    });
  }

  resendInvite(): void {
    this.resendingInvite.set(true);
    this.resendInviteMessage.set(null);
    this.resendInviteError.set(null);

    this.employeeService.resendInvite(this.employeeId).subscribe({
      next: (response) => {
        this.resendingInvite.set(false);
        this.resendInviteMessage.set(response.message);
      },
      error: (err) => {
        this.resendingInvite.set(false);
        this.resendInviteError.set(err.error?.message ?? '招待メールの再送信に失敗しました。');
      },
    });
  }
}
