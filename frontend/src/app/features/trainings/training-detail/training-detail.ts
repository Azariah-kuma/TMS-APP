import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingService } from '../../../core/services/training.service';
import { Employee } from '../../../core/models/employee';
import { Training } from '../../../core/models/training';

@Component({
  selector: 'app-training-detail',
  imports: [ReactiveFormsModule],
  templateUrl: './training-detail.html',
})
export class TrainingDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly fb = inject(FormBuilder);
  private readonly trainingService = inject(TrainingService);
  private readonly employeeService = inject(EmployeeService);
  private readonly enrollmentService = inject(TrainingEnrollmentService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly training = signal<Training | null>(null);
  readonly employees = signal<Employee[]>([]);
  readonly loading = signal(true);

  readonly lessonSubmitting = signal(false);
  readonly lessonForm = this.fb.nonNullable.group({
    title: ['', Validators.required],
  });

  readonly enrollSubmitting = signal(false);
  readonly enrollMessage = signal<string | null>(null);
  readonly enrollForm = this.fb.nonNullable.group({
    employee_id: [0, Validators.required],
    due_at: [''],
  });

  private trainingId = 0;

  ngOnInit(): void {
    this.trainingId = Number(this.route.snapshot.paramMap.get('id'));

    this.trainingService.get(this.trainingId).subscribe((training) => {
      this.training.set(training);
      this.loading.set(false);
    });

    if (this.isHr()) {
      this.employeeService.list().subscribe((employees) => this.employees.set(employees));
    }
  }

  addLesson(): void {
    if (this.lessonForm.invalid) {
      return;
    }

    this.lessonSubmitting.set(true);

    this.trainingService.addLesson(this.trainingId, this.lessonForm.getRawValue()).subscribe({
      next: () => {
        this.lessonForm.reset({ title: '' });
        this.lessonSubmitting.set(false);
        this.trainingService.get(this.trainingId).subscribe((training) => this.training.set(training));
      },
      error: () => this.lessonSubmitting.set(false),
    });
  }

  enrollEmployee(): void {
    if (this.enrollForm.invalid || !this.enrollForm.value.employee_id) {
      return;
    }

    this.enrollSubmitting.set(true);
    this.enrollMessage.set(null);

    const { employee_id, due_at } = this.enrollForm.getRawValue();

    this.enrollmentService.enroll(employee_id, this.trainingId, due_at || null).subscribe({
      next: () => {
        this.enrollSubmitting.set(false);
        this.enrollMessage.set('割り当てました。');
        this.enrollForm.reset({ employee_id: 0, due_at: '' });
      },
      error: (err) => {
        this.enrollSubmitting.set(false);
        this.enrollMessage.set(err.error?.message ?? '割り当てに失敗しました。');
      },
    });
  }
}
