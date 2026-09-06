import { FormControl, FormGroup, Validators } from '@angular/forms';
import { isFieldInvalid, toId } from './forms';

describe('isFieldInvalid', () => {
  function makeForm(): FormGroup {
    return new FormGroup({
      name: new FormControl('', Validators.required),
    });
  }

  it('コントロールが存在しない場合はfalseを返す', () => {
    const form = makeForm();
    expect(isFieldInvalid(form, 'unknown')).toBe(false);
  });

  it('未タッチ・未変更で無効な場合はfalseを返す（初期表示ではエラーを出さない）', () => {
    const form = makeForm();
    expect(isFieldInvalid(form, 'name')).toBe(false);
  });

  it('touchedかつ無効な場合はtrueを返す', () => {
    const form = makeForm();
    form.get('name')?.markAsTouched();
    expect(isFieldInvalid(form, 'name')).toBe(true);
  });

  it('dirtyかつ無効な場合はtrueを返す', () => {
    const form = makeForm();
    form.get('name')?.markAsDirty();
    expect(isFieldInvalid(form, 'name')).toBe(true);
  });

  it('touched/dirtyでも値が有効ならfalseを返す', () => {
    const form = makeForm();
    form.get('name')?.setValue('山田');
    form.get('name')?.markAsTouched();
    expect(isFieldInvalid(form, 'name')).toBe(false);
  });
});

describe('toId', () => {
  it('文字列の数値をnumberに変換する', () => {
    expect(toId('5')).toBe(5);
  });

  it('numberはそのまま返す', () => {
    expect(toId(5)).toBe(5);
  });

  it('空文字は0になる', () => {
    expect(toId('')).toBe(0);
  });

  it('数値でない文字列はNaNになる', () => {
    expect(toId('abc')).toBeNaN();
  });
});
