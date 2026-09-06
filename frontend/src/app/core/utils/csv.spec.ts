import { vi } from 'vitest';
import { downloadCsv, rowsToCsv } from './csv';

describe('rowsToCsv', () => {
  it('ヘッダーと行データをCSVに変換し、先頭にBOMを付与する', () => {
    const csv = rowsToCsv(['姓', '名'], [{ 姓: '山田', 名: '太郎' }]);

    expect(csv).toBe('﻿姓,名\r\n山田,太郎');
  });

  it('複数行を改行(\\r\\n)で連結する', () => {
    const csv = rowsToCsv(['姓'], [{ 姓: '山田' }, { 姓: '鈴木' }]);

    expect(csv).toBe('﻿姓\r\n山田\r\n鈴木');
  });

  it('行データに対応するヘッダーの値が無ければ空文字にする', () => {
    const csv = rowsToCsv(['姓', '名'], [{ 姓: '山田' }]);

    expect(csv).toBe('﻿姓,名\r\n山田,');
  });

  it('カンマを含む値はダブルクォートで囲む', () => {
    const csv = rowsToCsv(['メモ'], [{ メモ: 'a,b' }]);

    expect(csv).toBe('﻿メモ\r\n"a,b"');
  });

  it('ダブルクォートを含む値は二重にエスケープしてダブルクォートで囲む', () => {
    const csv = rowsToCsv(['メモ'], [{ メモ: '"重要"' }]);

    expect(csv).toBe('﻿メモ\r\n"""重要"""');
  });

  it('改行を含む値はダブルクォートで囲む', () => {
    const csv = rowsToCsv(['メモ'], [{ メモ: 'a\nb' }]);

    expect(csv).toBe('﻿メモ\r\n"a\nb"');
  });
});

describe('downloadCsv', () => {
  it('Blobを生成し、a要素経由でダウンロードをトリガーしてからURLを解放する', () => {
    const createObjectURLSpy = vi.spyOn(URL, 'createObjectURL').mockReturnValue('blob:mock-url');
    const revokeObjectURLSpy = vi.spyOn(URL, 'revokeObjectURL').mockImplementation(() => undefined);
    const clickSpy = vi.spyOn(HTMLAnchorElement.prototype, 'click').mockImplementation(() => undefined);

    downloadCsv('errors.csv', 'a,b\r\n1,2');

    expect(createObjectURLSpy).toHaveBeenCalledTimes(1);
    const blobArg = createObjectURLSpy.mock.calls[0][0] as Blob;
    expect(blobArg.type).toBe('text/csv;charset=utf-8;');
    expect(clickSpy).toHaveBeenCalledTimes(1);
    expect(revokeObjectURLSpy).toHaveBeenCalledWith('blob:mock-url');

    createObjectURLSpy.mockRestore();
    revokeObjectURLSpy.mockRestore();
    clickSpy.mockRestore();
  });
});
