import { TestBed } from '@angular/core/testing';
import { BarChart, BarChartRow } from './bar-chart';

describe('BarChart', () => {
  function createComponent(rows: BarChartRow[]) {
    const fixture = TestBed.createComponent(BarChart);
    fixture.componentRef.setInput('rows', rows);
    fixture.detectChanges();
    return fixture;
  }

  it('total()は3つのステータス件数の合計を返す', () => {
    const fixture = createComponent([]);
    const total = fixture.componentInstance.total({
      label: '研修A',
      not_started: 1,
      in_progress: 2,
      completed: 3,
    });

    expect(total).toBe(6);
  });

  it('percent()は合計に対する割合(%)を返す', () => {
    const fixture = createComponent([]);
    const row: BarChartRow = { label: '研修A', not_started: 1, in_progress: 1, completed: 2 };

    expect(fixture.componentInstance.percent(2, row)).toBe(50);
  });

  it('percent()は合計が0の場合、0を返す（0除算にならない）', () => {
    const fixture = createComponent([]);
    const row: BarChartRow = { label: '研修A', not_started: 0, in_progress: 0, completed: 0 };

    expect(fixture.componentInstance.percent(0, row)).toBe(0);
  });

  it('rowsが空の場合は「データがありません。」を表示する', () => {
    const fixture = createComponent([]);

    expect(fixture.nativeElement.textContent).toContain('データがありません。');
  });

  it('rowsがあれば、行ごとにラベルと合計人数を表示する', () => {
    const fixture = createComponent([{ label: '研修A', not_started: 1, in_progress: 2, completed: 3 }]);

    const text = fixture.nativeElement.textContent as string;
    expect(text).toContain('研修A');
    expect(text).toContain('6名');
  });
});
