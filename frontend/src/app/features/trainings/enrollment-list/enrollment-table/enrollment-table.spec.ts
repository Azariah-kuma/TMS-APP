import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { TrainingEnrollment } from '../../../../core/models/training-enrollment';
import { EnrollmentTable } from './enrollment-table';

function makeEnrollment(overrides: Partial<TrainingEnrollment> = {}): TrainingEnrollment {
  return {
    id: 1,
    employee_id: 1,
    employee_name: '山田太郎',
    training: { id: 1, title: '研修A' } as never,
    status: 'in_progress',
    progress: 50,
    due_at: null,
    started_at: null,
    completed_at: null,
    completed_lesson_ids: [],
    ...overrides,
  };
}

describe('EnrollmentTable', () => {
  function createComponent(enrollments: TrainingEnrollment[], showEmployeeColumn?: boolean) {
    TestBed.configureTestingModule({
      imports: [EnrollmentTable],
      providers: [provideRouter([])],
    });

    const fixture = TestBed.createComponent(EnrollmentTable);
    fixture.componentRef.setInput('enrollments', enrollments);
    if (showEmployeeColumn !== undefined) {
      fixture.componentRef.setInput('showEmployeeColumn', showEmployeeColumn);
    }
    fixture.detectChanges();
    return fixture;
  }

  it('受講記録が無ければその旨を表示する', () => {
    const fixture = createComponent([]);

    expect(fixture.nativeElement.textContent).toContain('受講記録はありません。');
  });

  it('受講記録一覧を保持する', () => {
    const enrollments = [makeEnrollment({ id: 1 }), makeEnrollment({ id: 2 })];
    const fixture = createComponent(enrollments);

    expect(fixture.componentInstance.enrollments()).toEqual(enrollments);
  });

  it('showEmployeeColumnは既定でfalse', () => {
    const fixture = createComponent([makeEnrollment()]);

    expect(fixture.componentInstance.showEmployeeColumn()).toBe(false);
  });

  it('showEmployeeColumnをtrueにすると対象者列を表示する', () => {
    const fixture = createComponent([makeEnrollment()], true);

    expect(fixture.componentInstance.showEmployeeColumn()).toBe(true);
    expect(fixture.nativeElement.textContent).toContain('対象者');
    expect(fixture.nativeElement.textContent).toContain('山田太郎');
  });
});
