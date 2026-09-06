import { toKatakana } from './kana';

describe('toKatakana', () => {
  it('ひらがなをカタカナに変換する', () => {
    expect(toKatakana('やまだたろう')).toBe('ヤマダタロウ');
  });

  it('既にカタカナの文字列はそのまま返す', () => {
    expect(toKatakana('ヤマダ')).toBe('ヤマダ');
  });

  it('ひらがなとそれ以外が混在していても、ひらがなの部分だけ変換する', () => {
    expect(toKatakana('ヤマダたろう123')).toBe('ヤマダタロウ123');
  });

  it('空文字はそのまま返す', () => {
    expect(toKatakana('')).toBe('');
  });
});
