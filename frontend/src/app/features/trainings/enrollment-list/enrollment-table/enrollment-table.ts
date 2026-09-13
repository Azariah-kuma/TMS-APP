import { Component, input } from '@angular/core';
import { RouterLink } from '@angular/router';
import { TrainingEnrollment } from '../../../../core/models/training-enrollment';
import { ProgressBar } from '../../../../shared/progress-bar/progress-bar';
import { statusLabel } from '../../../../shared/status-label';

/** 受講状況一覧の表本体。自分の分・部下の分など、表示内容ごとに分けて使えるよう切り出している。 */
@Component({
  selector: 'app-enrollment-table',
  imports: [RouterLink, ProgressBar],
  templateUrl: './enrollment-table.html',
})
export class EnrollmentTable {
  readonly enrollments = input.required<TrainingEnrollment[]>();
  /** 対象者（従業員名）の列を表示するか。自分の分だけの表では不要。 */
  readonly showEmployeeColumn = input(false);

  readonly statusLabel = statusLabel;
}
