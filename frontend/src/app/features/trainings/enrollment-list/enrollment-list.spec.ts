import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { EnrollmentList } from './enrollment-list';

function makeEnrollment(employeeId: number): TrainingEnrollment {
  return {
    id: employeeId,
    employee_id: employeeId,
    training: null,
    status: 'in_progress',
    progress: 0,
    due_at: null,
    started_at: null,
    completed_at: null,
    completed_lesson_ids: [],
  };
}

describe('EnrollmentList', () => {
  function createComponent(enrollments: TrainingEnrollment[], currentEmployeeId: number) {
    TestBed.configureTestingModule({
      imports: [EnrollmentList],
      providers: [
        provideRouter([]),
        { provide: TrainingEnrollmentService, useValue: { list: () => of(enrollments) } },
        { provide: AuthService, useValue: { currentEmployee: () => ({ id: currentEmployeeId }) } },
      ],
    });

    const fixture = TestBed.createComponent(EnrollmentList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に受講記録一覧を読み込む', () => {
    const fixture = createComponent([makeEnrollment(1)], 1);
    expect(fixture.componentInstance.enrollments()).toHaveLength(1);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('自分自身の受講記録のみの場合、対象者列は表示しない', () => {
    const fixture = createComponent([makeEnrollment(1)], 1);
    expect(fixture.componentInstance.showEmployeeColumn()).toBe(false);
  });

  it('自分以外の受講記録が含まれる場合、対象者列を表示する', () => {
    const fixture = createComponent([makeEnrollment(1), makeEnrollment(2)], 1);
    expect(fixture.componentInstance.showEmployeeColumn()).toBe(true);
  });
});
