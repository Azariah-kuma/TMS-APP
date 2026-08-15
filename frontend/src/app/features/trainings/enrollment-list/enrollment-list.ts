import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { ProgressBar } from '../../../shared/progress-bar/progress-bar';
import { statusLabel } from '../../../shared/status-label';

@Component({
  selector: 'app-enrollment-list',
  imports: [RouterLink, ProgressBar],
  templateUrl: './enrollment-list.html',
})
export class EnrollmentList implements OnInit {
  private readonly service = inject(TrainingEnrollmentService);
  private readonly auth = inject(AuthService);

  readonly statusLabel = statusLabel;
  readonly currentEmployeeId = this.auth.currentEmployee()?.id;

  readonly enrollments = signal<TrainingEnrollment[]>([]);
  readonly loading = signal(true);

  /** 自分以外の受講記録が含まれる（=上司または人事として見ている）場合のみ対象者列を表示する。 */
  readonly showEmployeeColumn = computed(() =>
    this.enrollments().some((e) => e.employee_id !== this.currentEmployeeId),
  );

  ngOnInit(): void {
    this.service.list().subscribe((enrollments) => {
      this.enrollments.set(enrollments);
      this.loading.set(false);
    });
  }
}
