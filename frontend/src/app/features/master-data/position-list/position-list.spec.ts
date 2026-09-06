import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Position } from '../../../core/models/position';
import { PositionList } from './position-list';

function makePosition(overrides: Partial<Position> = {}): Position {
  return { id: 1, name: '係長', code: 'POS-SC', rank: 1, ...overrides };
}

describe('PositionList', () => {
  function createComponent(masterDataMock: Partial<MasterDataService>, isHr = true) {
    TestBed.configureTestingModule({
      imports: [PositionList],
      providers: [
        { provide: MasterDataService, useValue: masterDataMock },
        { provide: AuthService, useValue: { isHr: () => isHr } },
      ],
    });

    const fixture = TestBed.createComponent(PositionList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時にpositions()を呼び、一覧を保持する', () => {
    const positions = [makePosition()];
    const fixture = createComponent({ positions: vi.fn().mockReturnValue(of(positions)) });

    expect(fixture.componentInstance.positions()).toEqual(positions);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  describe('submit (新規追加)', () => {
    it('無効なフォームでは何もしない', () => {
      const createPosition = vi.fn();
      const fixture = createComponent({ positions: () => of([]), createPosition });

      fixture.componentInstance.form.patchValue({ name: '', code: '' });
      fixture.componentInstance.submit();

      expect(createPosition).not.toHaveBeenCalled();
    });

    it('挿入位置が「先頭」(0)のままなら、after_position_idはnullで送る', () => {
      const createPosition = vi.fn().mockReturnValue(of(makePosition()));
      const fixture = createComponent({ positions: () => of([]), createPosition });

      fixture.componentInstance.form.setValue({ name: '課長', code: 'POS-MGR', after_position_id: 0 });
      fixture.componentInstance.submit();

      expect(createPosition).toHaveBeenCalledWith({ name: '課長', code: 'POS-MGR', after_position_id: null });
    });

    it('挿入位置を指定した場合、その役職IDをafter_position_idとして送る', () => {
      const createPosition = vi.fn().mockReturnValue(of(makePosition()));
      const fixture = createComponent({ positions: () => of([]), createPosition });

      fixture.componentInstance.form.setValue({ name: '班長', code: 'POS-TL', after_position_id: 5 });
      fixture.componentInstance.submit();

      expect(createPosition).toHaveBeenCalledWith({ name: '班長', code: 'POS-TL', after_position_id: 5 });
    });

    it('成功するとフォームをリセットし、一覧を再読み込みする', () => {
      const positionsSpy = vi.fn().mockReturnValue(of([]));
      const createPosition = vi.fn().mockReturnValue(of(makePosition()));
      const fixture = createComponent({ positions: positionsSpy, createPosition });

      fixture.componentInstance.form.setValue({ name: '課長', code: 'POS-MGR', after_position_id: 0 });
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.form.getRawValue()).toEqual({
        name: '',
        code: '',
        after_position_id: 0,
      });
      expect(positionsSpy).toHaveBeenCalledTimes(2); // 初期化時 + 再読み込み
      expect(fixture.componentInstance.submitting()).toBe(false);
    });

    it('失敗するとエラーメッセージを表示する', () => {
      const createPosition = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '重複しています。' } })));
      const fixture = createComponent({ positions: () => of([]), createPosition });

      fixture.componentInstance.form.setValue({ name: '課長', code: 'POS-MGR', after_position_id: 0 });
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.error()).toBe('重複しています。');
      expect(fixture.componentInstance.submitting()).toBe(false);
    });
  });

  describe('toggleActions / startEdit / cancelEdit', () => {
    it('toggleActionsは同じIDを渡すと閉じ、違うIDを渡すと開く', () => {
      const fixture = createComponent({ positions: () => of([]) });

      fixture.componentInstance.toggleActions(1);
      expect(fixture.componentInstance.openActionsId()).toBe(1);

      fixture.componentInstance.toggleActions(1);
      expect(fixture.componentInstance.openActionsId()).toBeNull();
    });

    it('startEditはeditFormに既存の値を反映し、editingIdをセットする', () => {
      const fixture = createComponent({ positions: () => of([]) });
      const position = makePosition({ id: 3, name: '主任', code: 'POS-JR' });

      fixture.componentInstance.startEdit(position);

      expect(fixture.componentInstance.editingId()).toBe(3);
      expect(fixture.componentInstance.editForm.getRawValue()).toEqual({ name: '主任', code: 'POS-JR' });
    });

    it('cancelEditはeditingIdをnullに戻す', () => {
      const fixture = createComponent({ positions: () => of([]) });

      fixture.componentInstance.startEdit(makePosition());
      fixture.componentInstance.cancelEdit();

      expect(fixture.componentInstance.editingId()).toBeNull();
    });
  });

  describe('submitEdit', () => {
    it('無効なフォームでは何もしない', () => {
      const updatePosition = vi.fn();
      const fixture = createComponent({ positions: () => of([]), updatePosition });

      fixture.componentInstance.editForm.patchValue({ name: '', code: '' });
      fixture.componentInstance.submitEdit(1);

      expect(updatePosition).not.toHaveBeenCalled();
    });

    it('成功すると編集状態を閉じ、一覧を再読み込みする', () => {
      const positionsSpy = vi.fn().mockReturnValue(of([]));
      const updatePosition = vi.fn().mockReturnValue(of(makePosition()));
      const fixture = createComponent({ positions: positionsSpy, updatePosition });

      fixture.componentInstance.openActionsId.set(1);
      fixture.componentInstance.editForm.setValue({ name: '主任', code: 'POS-JR' });
      fixture.componentInstance.submitEdit(1);

      expect(updatePosition).toHaveBeenCalledWith(1, { name: '主任', code: 'POS-JR' });
      expect(fixture.componentInstance.editingId()).toBeNull();
      expect(fixture.componentInstance.openActionsId()).toBeNull();
      expect(positionsSpy).toHaveBeenCalledTimes(2);
    });

    it('失敗するとeditErrorにメッセージを表示する', () => {
      const updatePosition = vi.fn().mockReturnValue(throwError(() => ({ error: { message: '訂正に失敗' } })));
      const fixture = createComponent({ positions: () => of([]), updatePosition });

      fixture.componentInstance.editForm.setValue({ name: '主任', code: 'POS-JR' });
      fixture.componentInstance.submitEdit(1);

      expect(fixture.componentInstance.editError()).toBe('訂正に失敗');
    });
  });

  describe('deletePosition', () => {
    it('確認ダイアログでキャンセルすると削除処理を行わない', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(false);
      const deletePosition = vi.fn();
      const fixture = createComponent({ positions: () => of([]), deletePosition });

      fixture.componentInstance.deletePosition(makePosition());

      expect(deletePosition).not.toHaveBeenCalled();
    });

    it('確認して成功すると、一覧を再読み込みしopenActionsIdを閉じる', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const positionsSpy = vi.fn().mockReturnValue(of([]));
      const deletePosition = vi.fn().mockReturnValue(of(undefined));
      const fixture = createComponent({ positions: positionsSpy, deletePosition });

      fixture.componentInstance.openActionsId.set(1);
      fixture.componentInstance.deletePosition(makePosition({ id: 1 }));

      expect(deletePosition).toHaveBeenCalledWith(1);
      expect(fixture.componentInstance.openActionsId()).toBeNull();
      expect(fixture.componentInstance.deletingId()).toBeNull();
      expect(positionsSpy).toHaveBeenCalledTimes(2);
    });

    it('削除に失敗すると（例: 使用中の役職）エラーメッセージを表示する', () => {
      vi.spyOn(window, 'confirm').mockReturnValue(true);
      const deletePosition = vi
        .fn()
        .mockReturnValue(throwError(() => ({ error: { message: 'この役職は使用されているため削除できません。' } })));
      const fixture = createComponent({ positions: () => of([]), deletePosition });

      fixture.componentInstance.deletePosition(makePosition({ id: 1 }));

      expect(fixture.componentInstance.deleteError()).toBe('この役職は使用されているため削除できません。');
      expect(fixture.componentInstance.deletingId()).toBeNull();
    });
  });
});
