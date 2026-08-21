import { FormGroup } from '@angular/forms';

/** フィールドがタッチ済みかつ無効なら、直下にエラーを表示すべきかどうかを返す。 */
export function isFieldInvalid(form: FormGroup, name: string): boolean {
  const control = form.get(name);

  if (!control) {
    return false;
  }

  return control.invalid && (control.dirty || control.touched);
}

/** HTMLの<select>やmultipart/form-dataは値を常に文字列として送るため、送信直前に数値へ変換する。 */
export function toId(value: unknown): number {
  return Number(value);
}
