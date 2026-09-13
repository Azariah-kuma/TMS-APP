/** 今日時点の会計年度（4/1〜翌3/31、開始年で表す）。バックエンドのApp\Support\FiscalYear::current()と同じ考え方。 */
export function currentFiscalYear(today: Date = new Date()): number {
  const month = today.getMonth() + 1;

  return month >= 4 ? today.getFullYear() : today.getFullYear() - 1;
}
