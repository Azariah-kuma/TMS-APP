import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingRequestService } from '../../../core/services/training-request.service';
import { TrainingService } from '../../../core/services/training.service';
import { Department } from '../../../core/models/department';
import { Employee } from '../../../core/models/employee';
import { Training } from '../../../core/models/training';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { TrainingRequest } from '../../../core/models/training-request';
import { requestStatusLabel } from '../../../shared/status-label';
import { toId } from '../../../core/utils/forms';

@Component({
  selector: 'app-training-detail',
  imports: [ReactiveFormsModule, RouterLink],
  templateUrl: './training-detail.html',
})
export class TrainingDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  private readonly trainingService = inject(TrainingService);
  private readonly employeeService = inject(EmployeeService);
  private readonly masterData = inject(MasterDataService);
  private readonly enrollmentService = inject(TrainingEnrollmentService);
  private readonly requestService = inject(TrainingRequestService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly requestStatusLabel = requestStatusLabel;
  readonly training = signal<Training | null>(null);
  readonly employees = signal<Employee[]>([]);
  readonly departments = signal<Department[]>([]);
  readonly loading = signal(true);

  /** ログイン中の従業員自身の、この研修への受講記録（あれば）。 */
  readonly myEnrollment = signal<TrainingEnrollment | null>(null);

  /** ログイン中の従業員自身の、この研修への申請のうち直近のもの（あれば）。 */
  readonly myRequest = signal<TrainingRequest | null>(null);

  readonly applySubmitting = signal(false);
  readonly applyError = signal<string | null>(null);
  readonly applyForm = this.fb.nonNullable.group({
    reason: [''],
    due_at: [''],
  });

  readonly lessonSubmitting = signal(false);
  readonly lessonError = signal<string | null>(null);
  readonly lessonContents = signal<File[]>([]);
  readonly lessonDeletingId = signal<number | null>(null);
  readonly lessonForm = this.fb.nonNullable.group({
    title: ['', Validators.required],
  });

  readonly enrollSubmitting = signal(false);
  readonly enrollMessage = signal<string | null>(null);
  readonly enrollForm = this.fb.nonNullable.group({
    scope: ['individual' as 'individual' | 'department' | 'company'],
    employee_id: [0],
    department_id: [0],
    due_at: [''],
  });

  readonly bulkEnrollSubmitting = signal(false);
  readonly bulkEnrollMessage = signal<string | null>(null);

  private trainingId = 0;

  ngOnInit(): void {
    this.trainingId = Number(this.route.snapshot.paramMap.get('id'));

    this.trainingService.get(this.trainingId).subscribe((training) => {
      this.training.set(training);
      this.loading.set(false);
    });

    this.enrollmentService.list().subscribe((enrollments) => {
      const employeeId = this.auth.currentEmployee()?.id;
      const mine = enrollments.find(
        (e) => e.employee_id === employeeId && e.training?.id === this.trainingId,
      );
      this.myEnrollment.set(mine ?? null);
    });

    if (!this.isHr()) {
      this.loadMyRequest();
    }

    if (this.isHr()) {
      this.employeeService.list().subscribe((employees) => this.employees.set(employees));
      this.masterData.departments().subscribe((departments) => this.departments.set(departments));
    }
  }

  private loadMyRequest(): void {
    this.requestService.list().subscribe((requests) => {
      const employeeId = this.auth.currentEmployee()?.id;
      const mine = requests.find(
        (r) => r.employee_id === employeeId && r.training?.id === this.trainingId,
      );
      this.myRequest.set(mine ?? null);
    });
  }

  applyForTraining(): void {
    this.applySubmitting.set(true);
    this.applyError.set(null);

    const raw = this.applyForm.getRawValue();

    this.requestService.create(this.trainingId, raw.reason || null, raw.due_at || null).subscribe({
      next: (request) => {
        this.applySubmitting.set(false);
        this.applyForm.reset({ reason: '', due_at: '' });
        this.myRequest.set(request);
      },
      error: (err) => {
        this.applySubmitting.set(false);
        this.applyError.set(err.error?.message ?? '申請に失敗しました。');
      },
    });
  }

  cancelMyRequest(): void {
    const request = this.myRequest();
    if (!request || !confirm('この申請を取り消しますか？')) {
      return;
    }

    this.requestService.cancel(request.id).subscribe((cancelled) => this.myRequest.set(cancelled));
  }

  onLessonContentSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);
    this.lessonContents.set(files);

    // Lesson名が未入力なら、1つ目のファイル名（拡張子を除く）を仮のLesson名として自動セットする。
    const titleControl = this.lessonForm.controls.title;
    if (files[0] && !titleControl.value) {
      titleControl.setValue(files[0].name.replace(/\.[^./]+$/, ''));
    }
  }

  addLesson(): void {
    if (this.lessonForm.invalid) {
      return;
    }

    this.lessonSubmitting.set(true);
    this.lessonError.set(null);

    this.trainingService
      .addLesson(this.trainingId, { ...this.lessonForm.getRawValue(), contents: this.lessonContents() })
      .subscribe({
        next: () => {
          this.lessonForm.reset({ title: '' });
          this.lessonContents.set([]);
          this.lessonSubmitting.set(false);
          this.trainingService.get(this.trainingId).subscribe((training) => this.training.set(training));
        },
        error: (err) => {
          this.lessonSubmitting.set(false);
          this.lessonError.set(err.error?.message ?? '教材の追加に失敗しました。');
        },
      });
  }

  deleteLesson(lessonId: number): void {
    if (!confirm('このLessonを削除しますか？受講者の完了記録も併せて削除されます。')) {
      return;
    }

    this.lessonDeletingId.set(lessonId);

    this.trainingService.deleteLesson(this.trainingId, lessonId).subscribe({
      next: () => {
        this.lessonDeletingId.set(null);
        this.trainingService.get(this.trainingId).subscribe((training) => this.training.set(training));
      },
      error: (err) => {
        this.lessonDeletingId.set(null);
        this.lessonError.set(err.error?.message ?? 'Lessonの削除に失敗しました。');
      },
    });
  }

  enrollEmployee(): void {
    const raw = this.enrollForm.getRawValue();
    const scope = raw.scope;
    const employeeId = toId(raw.employee_id);
    const departmentId = toId(raw.department_id);
    const due_at = raw.due_at;

    if (scope === 'individual') {
      if (!employeeId) {
        return;
      }

      this.enrollSubmitting.set(true);
      this.enrollMessage.set(null);

      this.enrollmentService.enroll(employeeId, this.trainingId, due_at || null).subscribe({
        next: () => {
          this.enrollSubmitting.set(false);
          this.enrollMessage.set('割り当てました。');
          this.enrollForm.patchValue({ employee_id: 0, due_at: '' });
        },
        error: (err) => {
          this.enrollSubmitting.set(false);
          this.enrollMessage.set(err.error?.message ?? '割り当てに失敗しました。');
        },
      });
      return;
    }

    if (scope === 'department' && !departmentId) {
      return;
    }

    this.bulkEnrollSubmitting.set(true);
    this.bulkEnrollMessage.set(null);

    const targetDepartmentId = scope === 'department' ? departmentId : null;

    this.enrollmentService.bulkEnroll(this.trainingId, targetDepartmentId, due_at || null).subscribe({
      next: ({ enrolled, skipped }) => {
        this.bulkEnrollSubmitting.set(false);
        this.bulkEnrollMessage.set(
          `${enrolled}名を割り当てました（既に登録済みで対象外: ${skipped}名）。`,
        );
        this.enrollForm.patchValue({ department_id: 0, due_at: '' });
      },
      error: (err) => {
        this.bulkEnrollSubmitting.set(false);
        this.bulkEnrollMessage.set(err.error?.message ?? '一括割り当てに失敗しました。');
      },
    });
  }
}
