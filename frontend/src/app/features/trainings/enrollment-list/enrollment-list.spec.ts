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
  function createComponent(
    enrollments: TrainingEnrollment[],
    currentEmployeeId: number,
    isHr = false,
  ) {
    TestBed.configureTestingModule({
      imports: [EnrollmentList],
      providers: [
        provideRouter([]),
        { provide: TrainingEnrollmentService, useValue: { list: () => of(enrollments) } },
        {
          provide: AuthService,
          useValue: { currentEmployee: () => ({ id: currentEmployeeId }), isHr: () => isHr },
        },
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

  it('自分自身の受講記録のみの場合、分割表示にはしない', () => {
    const fixture = createComponent([makeEnrollment(1)], 1);
    expect(fixture.componentInstance.showSplitView()).toBe(false);
  });

  it('上長として部下の受講記録が含まれる場合、自身/部下に分けて表示する', () => {
    const fixture = createComponent([makeEnrollment(1), makeEnrollment(2)], 1);

    expect(fixture.componentInstance.showSplitView()).toBe(true);
    expect(fixture.componentInstance.myEnrollments().map((e) => e.employee_id)).toEqual([1]);
    expect(fixture.componentInstance.subordinateEnrollments().map((e) => e.employee_id)).toEqual([2]);
  });

  it('人事の場合は部下に限らず全社員が対象のため、分割表示にはしない', () => {
    const fixture = createComponent([makeEnrollment(1), makeEnrollment(2)], 1, true);
    expect(fixture.componentInstance.showSplitView()).toBe(false);
  });
});
