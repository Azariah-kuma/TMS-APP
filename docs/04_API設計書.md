# API設計書

## 1. はじめに
本書は `02_詳細設計書.md` の「2. API仕様一覧」を補完し、各エンドポイントのリクエスト・レスポンスの具体的なスキーマを記述する。業務ルール・処理の流れは詳細設計書を参照。

## 2. 共通仕様

- **ベースURL**: 環境ごとの`APP_URL`（本番は`https://api.tms.example.com`を想定、`infra/terraform`参照）。
- **認証**: Laravel Sanctum SPA（Cookieベース・ステートフル）。状態変更リクエスト（POST/PATCH/PUT/DELETE）には`X-XSRF-TOKEN`ヘッダーが必須（`GET /sanctum/csrf-cookie`で事前取得したCookieの値を設定）。
- **Content-Type**: 通常は`application/json`。ファイルを伴うリクエスト（CSV一括登録、Lesson教材添付）のみ`multipart/form-data`。
- **共通エラーレスポンス**:

| ステータス | 発生条件 | ボディ形式 |
|---|---|---|
| 401 | 未ログイン | Laravel標準の認証エラー |
| 403 | 認可エラー（Policy/Gate不許可） | `{"message": "This action is unauthorized."}` |
| 404 | リソース不存在（`findOrFail`） | `{"message": "..."}` |
| 422 | 入力バリデーションエラー | `{"message": "...", "errors": {"field": ["..."]}}` |
| 422 | 業務ルール違反（ドメイン例外） | `{"message": "日本語の具体的なエラー文言"}` |

- **一覧系エンドポイント**: ページングは行わず、対象データ全件を配列で返す（件数が多くなる場合は今後の課題）。

---

## 3. 認証

### POST /login
リクエスト:
| フィールド | 型 | 必須 |
|---|---|---|
| email | string | ○ |
| password | string | ○ |

レスポンス 200: `UserResource`（下記参照）
エラー: 422（メールアドレス/パスワード不一致、`auth.failed`）

### POST /set-password
リクエスト:
| フィールド | 型 | 必須 |
|---|---|---|
| token | string | ○ |
| email | string | ○ |
| password | string | ○（8文字以上） |
| password_confirmation | string | ○（passwordと一致） |

レスポンス 200: `UserResource`
エラー: `InvalidPasswordResetTokenException`（422）

### POST /logout
レスポンス 204

### GET /user
レスポンス 200: `UserResource`

**UserResource**
| フィールド | 型 |
|---|---|
| id | int |
| name / name_kana | string（姓名・カナの連結） |
| last_name / first_name / last_name_kana / first_name_kana | string |
| email | string |
| employee | `EmployeeResource \| null` |

**EmployeeResource**
| フィールド | 型 |
|---|---|
| id | int |
| employee_code | string |
| name / name_kana / last_name / first_name / last_name_kana / first_name_kana | string |
| email | string |
| role | `"employee" \| "hr"` |
| hired_at | string（date） |
| retired_at | string（date）\| null |
| is_manager | boolean（現在1人以上の直属部下を持つか） |
| current_assignment | `EmployeeAssignmentResource \| null` |

---

## 4. 部署管理

### GET /departments
レスポンス 200: `Department[]`（`{id, name, code, created_at, updated_at}`の配列、name順）

### POST /departments
リクエスト: `{name: string, code: string}`（`code`はunique）
レスポンス 201: `Department`
エラー: 422（バリデーション）、403（人事以外）

### PATCH /departments/{department}
リクエスト: `{name: string, code: string}`（`code`は自分自身を除きunique）
レスポンス 200: `Department`
エラー: 422、403

### DELETE /departments/{department}
レスポンス 204
エラー: `DepartmentInUseException`（422、配属履歴または研修の閲覧対象部署として使用中）、403

---

## 5. 役職管理

### GET /positions
レスポンス 200: `Position[]`（`{id, name, code, rank, created_at, updated_at}`、rank順）

### POST /positions
リクエスト:
| フィールド | 型 | 必須 |
|---|---|---|
| name | string | ○ |
| code | string | ○（unique） |
| after_position_id | int, nullable | - （省略/nullで最上位に挿入） |

レスポンス 201: `Position`（挿入により決定したrankを含む）

### PATCH /positions/{position}
リクエスト: `{name: string, code: string}`（rankはここでは変更不可）
レスポンス 200: `Position`

### DELETE /positions/{position}
レスポンス 204
エラー: `PositionInUseException`（422、配属履歴で使用中）

---

## 6. 従業員管理

### GET /employees
人事のみ。レスポンス 200: `EmployeeResource[]`（`current_assignment`/`is_manager`含む）

### GET /employees/subordinates
ログイン中の従業員の部下一覧（階層＋有効な委任、退職者除く）。レスポンス 200: `EmployeeResource[]`

### GET /employees/{employee}
本人・上司（直接/間接/委任）・人事のみ。レスポンス 200: `EmployeeResource`

### POST /employees
人事のみ。リクエスト:
| フィールド | 型 | 必須 | 備考 |
|---|---|---|---|
| last_name / first_name | string | ○ | |
| last_name_kana / first_name_kana | string | ○ | 全角カタカナのみ |
| email | string | ○ | unique、招待メール送信先 |
| employee_code | string | ○ | unique |
| role | `"employee"\|"hr"` | ○ | |
| hired_at | string(date) | ○ | |
| department_id | int | ○ | |
| position_id | int | ○ | |
| manager_id | int, nullable | - | |

レスポンス 201: `EmployeeResource`

### POST /employees/bulk-import
人事のみ。`multipart/form-data`、フィールド`file`（CSV、最大2MB）。
レスポンス 200: `{created: EmployeeResource[], errors: {row: int, message: string, data: object}[]}`（部分成功）

### POST /employees/{employee}/resend-invite
人事のみ（throttle: 6回/分）。レスポンス 200: `{message: string}`
エラー: `InviteEmailFailedException`（422）

---

## 7. 異動管理

### GET /employees/{employee}/assignments
本人・上司・人事のみ。レスポンス 200: `EmployeeAssignmentResource[]`（新しい順、全履歴）

### POST /employees/{employee}/assignments
人事のみ。リクエスト:
| フィールド | 型 | 必須 |
|---|---|---|
| department_id | int | ○ |
| position_id | int | ○ |
| manager_id | int, nullable | - |
| started_at | string(date) | ○ |

レスポンス 201: `EmployeeAssignmentResource`
エラー: `EmployeeRetiredException`／`InvalidAssignmentPeriodException`（422）

**EmployeeAssignmentResource**
| フィールド | 型 |
|---|---|
| id | int |
| department_id / department_name | int / string |
| position_id / position_name | int / string |
| manager_id | int, nullable |
| started_at / ended_at | string(date), nullable |
| is_active | boolean（`ended_at IS NULL`） |

---

## 8. 委任管理

### GET /employees/{employee}/delegations
本人・上司・人事のみ。レスポンス 200: `DelegationResource[]`（委任元＝employee）

### POST /employees/{employee}/delegations
人事のみ。リクエスト:
| フィールド | 型 | 必須 |
|---|---|---|
| delegate_id | int | ○ |
| started_at | string(date) | ○ |
| ended_at | string(date), nullable | - |

レスポンス 201: `DelegationResource`
エラー: `InvalidDelegationException`／`EmployeeRetiredException`（422）

### DELETE /delegations/{delegation}
人事のみ。レスポンス 200（`ended_at`が即時終了に更新された`DelegationResource`。ハードデリートではない）

**DelegationResource**
| フィールド | 型 |
|---|---|
| id | int |
| delegator_id / delegate_id / delegate_name | int / int / string |
| started_at / ended_at | string(date), nullable |
| is_active | boolean |

---

## 9. 研修管理

### GET /trainings
`visibleTo`スコープで対象者に応じ絞込。レスポンス 200: `TrainingResource[]`（title順）

### POST /trainings
人事のみ。リクエスト:
| フィールド | 型 | 必須 | 備考 |
|---|---|---|---|
| title | string | ○ | |
| description | string, nullable | - | |
| category | string, nullable | - | |
| is_active | boolean | - | 既定true |
| audience_department_id | int, nullable | - | 対象部署 |
| audience_managers_only | boolean | - | 既定false |
| audience_new_hires_only | boolean | - | 既定false |
| requires_multistage_approval | boolean | - | 既定false |
| approval_stage_count | int(2-5), nullable | requires_multistage_approvalがtrueなら○ | |

レスポンス 201: `TrainingResource`

### GET /trainings/{training}
対象者の条件に合致する従業員のみ（人事は無条件）。レスポンス 200: `TrainingResource`（`lessons`含む）
エラー: 403（対象外）

### PATCH /trainings/{training}
人事のみ。POSTと同項目（`title`以外は`sometimes`）。レスポンス 200: `TrainingResource`

### DELETE /trainings/{training}
人事のみ。レスポンス 204

**TrainingResource**
| フィールド | 型 |
|---|---|
| id / title / description / category / is_active | |
| audience_department_id / audience_department_name | int, nullable / string, nullable |
| audience_managers_only / audience_new_hires_only | boolean |
| requires_multistage_approval / approval_stage_count | boolean / int, nullable |
| lessons | `TrainingLessonResource[]`（読み込み時のみ） |

---

## 10. 研修レッスン管理

### GET /trainings/{training}/lessons
全従業員可。レスポンス 200: `TrainingLessonResource[]`（position順）

### POST /trainings/{training}/lessons
人事のみ。教材ファイルが無ければ`application/json`、あれば`multipart/form-data`。
| フィールド | 型 | 必須 | 備考 |
|---|---|---|---|
| title | string | ○ | |
| position | int, nullable | - | |
| contents[] | file[] | - | 複数可。動画(mp4/mov/webm/m4v)・PDF・PPT・Word・画像、1ファイル最大100MB |

レスポンス 201: `TrainingLessonResource`

### DELETE /trainings/{training}/lessons/{trainingLesson}
人事のみ。レスポンス 204
エラー: `InvalidTrainingLessonException`（422、Lessonが指定研修に属さない）

**TrainingLessonResource**
| フィールド | 型 |
|---|---|
| id / training_id / title / position | |
| attachments | `TrainingLessonAttachmentResource[]` |

**TrainingLessonAttachmentResource**
| フィールド | 型 |
|---|---|
| id | int |
| url | string（公開URL） |
| original_name / mime_type | string |

---

## 11. 受講登録・進捗

### GET /training-enrollments
`visibleTo`スコープ（本人／部下／全件）。レスポンス 200: `TrainingEnrollmentResource[]`

### GET /training-enrollments/{trainingEnrollment}
本人・上司・人事のみ。レスポンス 200: `TrainingEnrollmentResource`

### POST /employees/{employee}/training-enrollments
人事のみ。リクエスト: `{training_id: int, due_at: string(date), nullable}`
レスポンス 201: `TrainingEnrollmentResource`
エラー: `AlreadyEnrolledException`／`EmployeeRetiredException`（422）

### POST /trainings/{training}/bulk-enroll
人事のみ。リクエスト: `{department_id: int, nullable（省略で全社一括）, due_at: string(date), nullable}`
レスポンス 200: `{enrolled: int, skipped: int}`

### PATCH /training-enrollments/{trainingEnrollment}
本人のみ（Lesson未定義の研修限定）。リクエスト: `{progress: int(0-100)}`
レスポンス 200: `TrainingEnrollmentResource`
エラー: `LessonBasedProgressException`（422、Lesson定義済み研修に対する手動更新）

### DELETE /training-enrollments/{trainingEnrollment}
人事のみ。レスポンス 204

### PUT /training-enrollments/{trainingEnrollment}/lessons/{trainingLesson}
本人のみ。レスポンス 200: `TrainingEnrollmentResource`（`completed_lesson_ids`に反映）
エラー: `InvalidTrainingLessonException`（422）

### DELETE /training-enrollments/{trainingEnrollment}/lessons/{trainingLesson}
本人のみ。レスポンス 200: `TrainingEnrollmentResource`

**TrainingEnrollmentResource**
| フィールド | 型 |
|---|---|
| id / employee_id / employee_name | |
| training | `TrainingResource \| null` |
| status | `"not_started"\|"in_progress"\|"completed"` |
| progress | int(0-100) |
| due_at / started_at / completed_at | string, nullable |
| completed_lesson_ids | `int[]`（読み込み時のみ） |

---

## 12. 研修申請・承認ワークフロー

### GET /training-requests
`visibleTo`スコープ。レスポンス 200: `TrainingRequestResource[]`（新しい順）

### POST /training-requests
リクエスト:
| フィールド | 型 | 必須 | 備考 |
|---|---|---|---|
| employee_id | int, nullable | - | 省略で自己申請、指定で部下の代理申請 |
| training_id | int | ○ | |
| reason | string, nullable | - | 最大1000文字 |
| due_at | string(date), nullable | - | |

レスポンス 201: `TrainingRequestResource`
エラー: `AlreadyEnrolledException`／`AlreadyRequestedException`／`EmployeeRetiredException`（422）

### GET /training-requests/{trainingRequest}
本人・上司・人事のみ。レスポンス 200: `TrainingRequestResource`

### POST /trainings/{training}/bulk-request
部下を持つ上司または人事。リクエスト: `{department_id: int}`
レスポンス 200: `{requested: int, skipped: int}`

### POST /training-requests/{trainingRequest}/approve
自己申請かつ現在の決裁対象者の上司、または人事。レスポンス 200: `TrainingRequestResource`
エラー: `TrainingRequestNotPendingException`（422）、403（代理申請／権限外）

### POST /training-requests/bulk-approve
リクエスト: `{ids: int[]}`（承認権限の無いID・非pendingのIDは黙ってスキップ）
レスポンス 200: `{approved: int, skipped: int}`

### POST /training-requests/{trainingRequest}/reject
approveと同条件。リクエスト: `{comment: string, nullable}`（最大1000文字）
レスポンス 200: `TrainingRequestResource`

### DELETE /training-requests/{trainingRequest}
対象者本人または申請した本人のみ。レスポンス 200: `TrainingRequestResource`（status: cancelled）

**TrainingRequestResource**
| フィールド | 型 |
|---|---|
| id / employee_id / employee_name | |
| requested_by_employee_id / requested_by_name | int / string, nullable |
| is_self_requested | boolean |
| training | `TrainingResource \| null` |
| status | `"pending"\|"approved"\|"rejected"\|"cancelled"` |
| reason / due_at | string, nullable |
| required_approval_stages / current_approval_stage | int |
| approval_history | `TrainingRequestApprovalStageResource[]` |
| decided_by_employee_id / decided_by_name / decided_at / decision_comment | |
| requested_at | string(datetime) |
| can_decide / can_cancel | boolean（サーバー側Policy判定結果） |
| already_enrolled | boolean（読み込み時のみ） |

**TrainingRequestApprovalStageResource**
| フィールド | 型 |
|---|---|
| stage_number | int |
| decided_by_name | string |
| status | `"approved"\|"rejected"` |
| decided_at | string(datetime) |
| comment | string, nullable |

---

## 13. レポート

### GET /reports/training-summary
人事のみ。レスポンス 200:
```json
{
  "by_training": [{ "id": 1, "name": "研修A", "not_started": 3, "in_progress": 5, "completed": 10 }],
  "by_department": [{ "id": 1, "name": "開発部", "not_started": 2, "in_progress": 4, "completed": 8 }]
}
```

### GET /reports/training-enrollments.csv
人事のみ。レスポンス 200: `text/csv; charset=UTF-8`（`Content-Disposition: attachment`）。列: 従業員コード/氏名/部署/役職/研修/ステータス/進捗/期限/完了日時。CSVインジェクション対策済み（3.11参照）。
