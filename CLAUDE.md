# プロジェクトルール（絶対遵守）

- フロントエンド（Angular）はメイン実装者としてClaudeが作成を担当する。バックエンド（PHP）側のコード修正に応じて、
  フロントエンドも合わせて作成・更新すること。

## コミュニケーション
- README、コード内コメント、回答は基本的に日本語で書くこと。

## Git運用
- ユーザーから明示的な指示があるまで `git push` は絶対に行わないこと。
  ある程度作り込んでからまとめてpushする方針。

## コーディングスタイル
- コードの参考（見本）は [spatie/freek.dev](https://github.com/spatie/freek.dev) を参考にすること。
  この参考リポジトリとの整合性・スタイルの妥当性は、レビュー・テスト担当に確認してもらう想定とする。
- **Fat Controllerを作らない**: Controllerはリクエストの受け取りとレスポンス返却に専念し、
  ロジックは Actions や Services に委譲する。
- **FormRequestの徹底**: 入力データのバリデーションはすべて独立した FormRequest クラスで行う。
  Controller内で直接 `$request->validate()` しない。
- **型宣言の徹底**: 引数・戻り値の型定義（Type Hinting）を厳密に行う（`declare(strict_types=1)` の使用も検討）。
- **意図の伝わるテスト**: `tests/Feature` や `tests/Unit` のテストを読むだけで仕様が理解できるように書く
  （テスト名・Arrange-Act-Assertの構成を意識する）。
- **セキュリティ**: 常にセキュリティ対策（SQLインジェクション、XSS、CSRF、認可漏れなど）を考慮したコードを書く。
