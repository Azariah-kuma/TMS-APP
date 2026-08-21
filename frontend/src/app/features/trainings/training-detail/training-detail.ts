import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingService } from '../../../core/services/training.service';
import { Department } from '../../../core/models/department';
import { Employee } from '../../../core/models/employee';
import { Training } from '../../../core/models/training';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
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
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly training = signal<Training | null>(null);
  readonly employees = signal<Employee[]>([]);
  readonly departments = signal<Department[]>([]);
  readonly loading = signal(true);

  /** ログイン中の従業員自身の、この研修への受講記録（あれば）。 */
  readonly myEnrollment = signal<TrainingEnrollment | null>(null);

  readonly lessonSubmitting = signal(false);
  readonly lessonError = signal<string | null>(null);
  readonly lessonContent = signal<File | null>(null);
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

    if (this.isHr()) {
      this.employeeService.list().subscribe((employees) => this.employees.set(employees));
      this.masterData.departments().subscribe((departments) => this.departments.set(departments));
    }
  }

  onLessonContentSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;
    this.lessonContent.set(file);

    // Lesson名が未入力なら、ファイル名（拡張子を除く）を仮のLesson名として自動セットする。
    const titleControl = this.lessonForm.controls.title;
    if (file && !titleControl.value) {
      titleControl.setValue(file.name.replace(/\.[^./]+$/, ''));
    }
  }

  addLesson(): void {
    if (this.lessonForm.invalid) {
      return;
    }

    this.lessonSubmitting.set(true);
    this.lessonError.set(null);

    this.trainingService
      .addLesson(this.trainingId, { ...this.lessonForm.getRawValue(), content: this.lessonContent() })
      .subscribe({
        next: () => {
          this.lessonForm.reset({ title: '' });
          this.lessonContent.set(null);
          this.lessonSubmitting.set(false);
          this.trainingService.get(this.trainingId).subscribe((training) => this.training.set(training));
        },
        error: (err) => {
          this.lessonSubmitting.set(false);
          this.lessonError.set(err.error?.message ?? '教材の追加に失敗しました。');
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
