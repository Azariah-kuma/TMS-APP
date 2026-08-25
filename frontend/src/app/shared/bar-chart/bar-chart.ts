import { Component, input } from '@angular/core';

export interface BarChartRow {
  label: string;
  not_started: number;
  in_progress: number;
  completed: number;
}

/** 研修受講ステータス内訳（未着手・受講中・完了）を積み上げ横棒グラフで表示する。 */
@Component({
  selector: 'app-bar-chart',
  templateUrl: './bar-chart.html',
})
export class BarChart {
  readonly rows = input.required<BarChartRow[]>();

  total(row: BarChartRow): number {
    return row.not_started + row.in_progress + row.completed;
  }

  percent(value: number, row: BarChartRow): number {
    const total = this.total(row);
    return total === 0 ? 0 : (value / total) * 100;
  }
}
