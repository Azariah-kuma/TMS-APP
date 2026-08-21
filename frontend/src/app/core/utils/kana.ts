/** ひらがな（U+3041-U+3096）をカタカナに変換する。それ以外の文字はそのまま返す。 */
export function toKatakana(input: string): string {
  return input.replace(/[ぁ-ゖ]/g, (char) => String.fromCharCode(char.charCodeAt(0) + 0x60));
}
