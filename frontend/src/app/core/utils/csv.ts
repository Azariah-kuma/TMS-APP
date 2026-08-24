/** カンマ・ダブルクォート・改行を含む値をCSVのフィールドとして正しくエスケープする。 */
function escapeCsvField(value: string): string {
  if (/[",\r\n]/.test(value)) {
    return `"${value.replace(/"/g, '""')}"`;
  }
  return value;
}

/** ヘッダー名 => 値 の行データ一覧を、Excelでも文字化けしないCSV文字列（BOM付き）に変換する。 */
export function rowsToCsv(headers: string[], rows: Record<string, string>[]): string {
  const lines = [
    headers.map(escapeCsvField).join(','),
    ...rows.map((row) => headers.map((header) => escapeCsvField(row[header] ?? '')).join(',')),
  ];

  const byteOrderMark = String.fromCharCode(0xfeff);

  return byteOrderMark + lines.join('\r\n');
}

/** CSV文字列をファイルとしてブラウザにダウンロードさせる。 */
export function downloadCsv(filename: string, csv: string): void {
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);

  const link = document.createElement('a');
  link.href = url;
  link.download = filename;
  link.click();

  URL.revokeObjectURL(url);
}
