import { currentFiscalYear } from './fiscal-year';

describe('currentFiscalYear', () => {
  it('4月以降は当年を返す', () => {
    expect(currentFiscalYear(new Date(2026, 3, 1))).toBe(2026);
  });

  it('1〜3月は前年を返す', () => {
    expect(currentFiscalYear(new Date(2027, 2, 31))).toBe(2026);
  });
});
