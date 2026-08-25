import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { TrainingRequestService } from '../../../core/services/training-request.service';
import { TrainingService } from '../../../core/services/training.service';
import { Employee } from '../../../core/models/employee';
import { Training } from '../../../core/models/training';
import { TrainingRequest } from '../../../core/models/training-request';
import { requestStatusLabel } from '../../../shared/status-label';
import { toId } from '../../../core/utils/forms';

@Component({
  selector: 'app-training-request-list',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './training-request-list.html',
})
export class TrainingRequestList implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(TrainingRequestService);
  private readonly trainingService = inject(TrainingService);
  private readonly employeeService = inject(EmployeeService);
  private readonly auth = inject(AuthService);

  readonly requestStatusLabel = requestStatusLabel;
  /** ログイン中の従業員IDへの参照は、user()の更新に追従できるよう都度算出する。 */
  readonly currentEmployeeId = computed(() => this.auth.currentEmployee()?.id);

  readonly requests = signal<TrainingRequest[]>([]);
  readonly trainings = signal<Training[]>([]);
  readonly loading = signal(true);

  /** アコーディオンで同時に開ける申請フォームは1つだけ。 */
  readonly activePanel = signal<'self' | 'delegate' | null>(null);

  togglePanel(panel: 'self' | 'delegate'): void {
    this.activePanel.set(this.activePanel() === panel ? null : panel);
  }

  readonly applySubmitting = signal(false);
  readonly applyError = signal<string | null>(null);
  readonly applyForm = this.fb.nonNullable.group({
    training_id: [0, Validators.required],
    reason: [''],
    due_at: [''],
  });

  /** 部下を持つ、人事ではないログイン中の従業員（＝代理申請フォームを表示する対象）。 */
  readonly isManager = computed(() => !this.auth.isHr() && (this.auth.currentEmployee()?.is_manager ?? false));
  readonly subordinates = signal<Employee[]>([]);

  readonly delegateSubmitting = signal(false);
  readonly delegateMessage = signal<string | null>(null);
  readonly delegateForm = this.fb.nonNullable.group({
    scope: ['individual' as 'individual' | 'department'],
    training_id: [0, Validators.required],
    employee_id: [0],
    reason: [''],
    due_at: [''],
  });

  readonly actioningId = signal<number | null>(null);

  /** 承認待ちで、かつ自分に承認権限がある申請のうち、一括承認のために選択したもの。 */
  readonly selectedIds = signal<ReadonlySet<number>>(new Set());
  readonly bulkApproving = signal(false);
  readonly bulkApproveMessage = signal<string | null>(null);

  /** 自分以外の申請が含まれる（=上司または人事として見ている）場合のみ申請者列を表示する。 */
  readonly showEmployeeColumn = computed(() =>
    this.requests().some((r) => r.employee_id !== this.currentEmployeeId()),
  );

  ngOnInit(): void {
    this.loadRequests();
    this.trainingService.list().subscribe((trainings) => this.trainings.set(trainings));

    if (this.isManager()) {
      this.employeeService.subordinates().subscribe((subordinates) => this.subordinates.set(subordinates));
    }
  }

  private loadRequests(): void {
    this.service.list().subscribe((requests) => {
      this.requests.set(requests);
      this.loading.set(false);
      this.selectedIds.set(new Set());
    });
  }

  isSelectable(request: TrainingRequest): boolean {
    return request.can_decide && request.status === 'pending';
  }

  isSelected(id: number): boolean {
    return this.selectedIds().has(id);
  }

  toggleSelect(id: number): void {
    const next = new Set(this.selectedIds());
    if (next.has(id)) {
      next.delete(id);
    } else {
      next.add(id);
    }
    this.selectedIds.set(next);
  }

  bulkApprove(): void {
    const ids = Array.from(this.selectedIds());
    if (ids.length === 0) {
      return;
    }

    this.bulkApproving.set(true);
    this.bulkApproveMessage.set(null);

    this.service.bulkApprove(ids).subscribe({
      next: ({ approved, skipped }) => {
        this.bulkApproving.set(false);
        this.bulkApproveMessage.set(`${approved}件を承認しました（対象外: ${skipped}件）。`);
        this.loadRequests();
      },
      error: (err) => {
        this.bulkApproving.set(false);
        this.bulkApproveMessage.set(err.error?.message ?? '一括承認に失敗しました。');
      },
    });
  }

  submitApply(): void {
    if (this.applyForm.invalid) {
      return;
    }

    this.applySubmitting.set(true);
    this.applyError.set(null);

    const raw = this.applyForm.getRawValue();

    this.service.create(toId(raw.training_id), raw.reason || null, raw.due_at || null).subscribe({
      next: () => {
        this.applySubmitting.set(false);
        this.activePanel.set(null);
        this.applyForm.reset({ training_id: 0, reason: '', due_at: '' });
        this.loadRequests();
      },
      error: (err) => {
        this.applySubmitting.set(false);
        this.applyError.set(err.error?.message ?? '申請に失敗しました。');
      },
    });
  }

  submitDelegate(): void {
    const raw = this.delegateForm.getRawValue();
    const trainingId = toId(raw.training_id);
    if (!trainingId) {
      return;
    }

    if (raw.scope === 'individual') {
      const employeeId = toId(raw.employee_id);
      if (!employeeId) {
        return;
      }

      this.delegateSubmitting.set(true);
      this.delegateMessage.set(null);

      this.service.create(trainingId, raw.reason || null, raw.due_at || null, employeeId).subscribe({
        next: () => {
          this.delegateSubmitting.set(false);
          this.delegateMessage.set('申請しました（人事の承認待ちです）。');
          this.delegateForm.patchValue({ employee_id: 0, reason: '', due_at: '' });
          this.loadRequests();
        },
        error: (err) => {
          this.delegateSubmitting.set(false);
          this.delegateMessage.set(err.error?.message ?? '申請に失敗しました。');
        },
      });
      return;
    }

    const departmentId = this.auth.currentEmployee()?.current_assignment?.department_id;
    if (!departmentId) {
      return;
    }

    this.delegateSubmitting.set(true);
    this.delegateMessage.set(null);

    this.service.bulkRequest(trainingId, departmentId).subscribe({
      next: ({ requested, skipped }) => {
        this.delegateSubmitting.set(false);
        this.delegateMessage.set(
          `${requested}名を申請しました（既に受講登録・申請済みで対象外: ${skipped}名）。人事の承認待ちです。`,
        );
        this.loadRequests();
      },
      error: (err) => {
        this.delegateSubmitting.set(false);
        this.delegateMessage.set(err.error?.message ?? '一括申請に失敗しました。');
      },
    });
  }

  approve(id: number): void {
    this.actioningId.set(id);

    this.service.approve(id).subscribe({
      next: () => {
        this.actioningId.set(null);
        this.loadRequests();
      },
      error: () => this.actioningId.set(null),
    });
  }

  reject(id: number): void {
    const comment = prompt('却下理由（任意）');
    if (comment === null) {
      return;
    }

    this.actioningId.set(id);

    this.service.reject(id, comment || null).subscribe({
      next: () => {
        this.actioningId.set(null);
        this.loadRequests();
      },
      error: () => this.actioningId.set(null),
    });
  }

  cancel(id: number): void {
    if (!confirm('この申請を取り消しますか？')) {
      return;
    }

    this.actioningId.set(id);

    this.service.cancel(id).subscribe({
      next: () => {
        this.actioningId.set(null);
        this.loadRequests();
      },
      error: () => this.actioningId.set(null),
    });
  }
}
