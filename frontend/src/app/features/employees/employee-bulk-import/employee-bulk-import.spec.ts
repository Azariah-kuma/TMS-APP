import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { EmployeeService } from '../../../core/services/employee.service';
import { EmployeeBulkImport } from './employee-bulk-import';

describe('EmployeeBulkImport', () => {
  function createComponent(employeeServiceMock: Partial<EmployeeService> = {}) {
    TestBed.configureTestingModule({
      imports: [EmployeeBulkImport],
      providers: [provideRouter([]), { provide: EmployeeService, useValue: employeeServiceMock }],
    });

    const fixture = TestBed.createComponent(EmployeeBulkImport);
    fixture.detectChanges();
    return fixture;
  }

  function fakeFileInputEvent(file: File | null): Event {
    return { target: { files: file ? [file] : [] } } as unknown as Event;
  }

  describe('onFileSelected', () => {
    it('選択したファイルを保持し、既存の結果・エラーをクリアする', () => {
      const fixture = createComponent();
      const file = new File(['a'], 'employees.csv', { type: 'text/csv' });

      fixture.componentInstance.error.set('前回のエラー');
      fixture.componentInstance.onFileSelected(fakeFileInputEvent(file));

      expect(fixture.componentInstance.file()).toBe(file);
      expect(fixture.componentInstance.error()).toBeNull();
      expect(fixture.componentInstance.result()).toBeNull();
    });

    it('ファイルが選択されていなければnullにする', () => {
      const fixture = createComponent();
      fixture.componentInstance.onFileSelected(fakeFileInputEvent(null));

      expect(fixture.componentInstance.file()).toBeNull();
    });
  });

  describe('submit', () => {
    it('ファイル未選択では何もしない', () => {
      const bulkImport = vi.fn();
      const fixture = createComponent({ bulkImport });

      fixture.componentInstance.submit();

      expect(bulkImport).not.toHaveBeenCalled();
    });

    it('成功すると結果を保持する', () => {
      const file = new File(['a'], 'employees.csv', { type: 'text/csv' });
      const result = { created: [], errors: [] };
      const bulkImport = vi.fn().mockReturnValue(of(result));
      const fixture = createComponent({ bulkImport });

      fixture.componentInstance.onFileSelected(fakeFileInputEvent(file));
      fixture.componentInstance.submit();

      expect(bulkImport).toHaveBeenCalledWith(file);
      expect(fixture.componentInstance.result()).toEqual(result);
      expect(fixture.componentInstance.submitting()).toBe(false);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const file = new File(['a'], 'employees.csv', { type: 'text/csv' });
      const bulkImport = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '不正な形式です。' } })));
      const fixture = createComponent({ bulkImport });

      fixture.componentInstance.onFileSelected(fakeFileInputEvent(file));
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.error()).toBe('不正な形式です。');
    });
  });

  describe('downloadFailedRows', () => {
    it('エラーが無ければダウンロードしない', () => {
      const fixture = createComponent();
      const createObjectURLSpy = vi.spyOn(URL, 'createObjectURL');

      fixture.componentInstance.downloadFailedRows();

      expect(createObjectURLSpy).not.toHaveBeenCalled();
      createObjectURLSpy.mockRestore();
    });

    it('エラーがあれば、元のCSV列見出しで失敗行だけのCSVをダウンロードする', () => {
      const fixture = createComponent();
      const createObjectURLSpy = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:mock-url');
      const revokeObjectURLSpy = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);
      const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => undefined);

      fixture.componentInstance.result.set({
        created: [],
        errors: [
          { row: 2, message: '部署コードが不正です。', data: { 姓: '山田', 部署コード: 'XXX' } },
        ],
      });

      fixture.componentInstance.downloadFailedRows();

      expect(createObjectURLSpy).toHaveBeenCalledTimes(1);
      expect(clickSpy).toHaveBeenCalledTimes(1);

      createObjectURLSpy.mockRestore();
      revokeObjectURLSpy.mockRestore();
      clickSpy.mockRestore();
    });
  });
});
