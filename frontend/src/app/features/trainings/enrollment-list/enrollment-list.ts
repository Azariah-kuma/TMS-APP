import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { EnrollmentTable } from './enrollment-table/enrollment-table';

@Component({
  selector: 'app-enrollment-list',
  imports: [EnrollmentTable],
  templateUrl: './enrollment-list.html',
})
export class EnrollmentList implements OnInit {
  private readonly service = inject(TrainingEnrollmentService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  /** ログイン中の従業員IDへの参照は、user()の更新に追従できるよう都度算出する。 */
  readonly currentEmployeeId = computed(() => this.auth.currentEmployee()?.id);

  readonly enrollments = signal<TrainingEnrollment[]>([]);
  readonly loading = signal(true);

  readonly myEnrollments = computed(() =>
    this.enrollments().filter((e) => e.employee_id === this.currentEmployeeId()),
  );

  readonly subordinateEnrollments = computed(() =>
    this.enrollments().filter((e) => e.employee_id !== this.currentEmployeeId()),
  );

  /**
   * 上長として部下の受講記録も見えている場合は、自身/部下を分けて表示する。
   * 人事は部下に限らず全社員が対象になるため、この区別に意味が無く対象外とする。
   */
  readonly showSplitView = computed(() => !this.isHr() && this.subordinateEnrollments().length > 0);

  ngOnInit(): void {
    this.service.list().subscribe((enrollments) => {
      this.enrollments.set(enrollments);
      this.loading.set(false);
    });
  }
}
