# インフラ（Terraform / AWS）

tms-app の本番環境をAWS上にTerraformで構築するためのコード一式。

## 全体構成

```
インターネット
  │
  ├─ https://tms.example.com  ──▶ CloudFront ──▶ S3（Angular SPAの静的ビルド）
  ├─ https://files.example.com ─▶ CloudFront ──▶ S3（研修教材ファイル）
  └─ https://api.tms.example.com ▶ ALB ──▶ ECS Fargate（Laravel API, web/nginx+php-fpm）
                                              │
                                              ├─ ECS Fargate（キューワーカー: queue:work）
                                              ├─ EventBridge Scheduler → ECS RunTask（5分おき: schedule:run）
                                              └─ RDS PostgreSQL（プライベートサブネット）

SES: 招待メール・リマインド通知等の送信（アプリはIAMタスクロール経由でAPI送信、SMTP資格情報は発行しない）
```

- **セッション・キャッシュ・キュー**はいずれもDB（`database`ドライバ）を使用しており、Redis/ElastiCacheは導入していない（現状のアプリがRedisに依存していないため。将来的にキューの負荷が高くなればSQS化等を検討）。
- **フロントエンド（Angular SPA）とバックエンド（Laravel API）は分離**しており、フロントエンドはS3+CloudFrontで静的配信、バックエンドのみECS Fargateで稼働する。

## ディレクトリ構成

```
infra/terraform/
├── bootstrap/            Terraform state保存用のS3バケット・DynamoDBロックテーブル（最初に一度だけ手動apply）
├── modules/
│   ├── network/           VPC・サブネット・NAT Gateway
│   ├── database/          RDS PostgreSQL・Secrets Manager
│   ├── storage/           S3 + CloudFront（研修教材ファイル／フロントエンドSPA）
│   ├── ecs/                ECRリポジトリ・ECSクラスタ・ALB・Webサービス・キューワーカー・スケジューラ
│   ├── dns/                Route53参照・ACM証明書（ALB用／CloudFront用）
│   └── mail/                SESドメイン認証（DKIM・MAIL FROM）
└── environments/
    └── production/         上記モジュールを結線する本番環境のルートモジュール
```

`docker/production/` に、ECSで実行するLaravelアプリの本番用Dockerfile（Nginx + PHP-FPM）一式がある。

## 事前準備

1. **ドメインの取得・Route53への登録**（このコードの対象外）。既にRoute53にパブリックホストゾーンが存在すること。
2. AWS CLIの認証情報を用意し、対象のAWSアカウントに `terraform apply` できる権限を持つIAMユーザー/ロールでログインしておく。
3. Terraform CLI（1.9以降）をインストールしておく。

## 手順

### 1. state保存用リソースの作成（最初の1回だけ）

```sh
cd infra/terraform/bootstrap
terraform init
terraform apply
```

出力される `state_bucket_name` / `lock_table_name` を、`environments/production/backend.tf` の `bucket` / `dynamodb_table` に設定する（既にデフォルト値として埋めてあるので、バケット名を変えない限り変更不要）。

### 2. 変数の設定

```sh
cd infra/terraform/environments/production
cp terraform.tfvars.example terraform.tfvars
```

`terraform.tfvars` を編集し、ドメイン名・S3バケット名（グローバル一意）等を実際の値に書き換える。

### 3. インフラの構築

```sh
terraform init
terraform plan   # 内容を確認
terraform apply
```

初回applyの時点ではECRにイメージが無いため、ECSサービスのタスクは起動に失敗し続ける（正常）。次の手順でイメージをpushしてから安定する。

### 4. アプリケーションイメージのビルド・push

```sh
# リポジトリルートで
ECR_URL=$(terraform -chdir=infra/terraform/environments/production output -raw ecr_repository_url)
aws ecr get-login-password --region ap-northeast-1 | docker login --username AWS --password-stdin "${ECR_URL%/*}"

docker build -f docker/production/Dockerfile -t "$ECR_URL:latest" .
docker push "$ECR_URL:latest"

aws ecs update-service --cluster "$(terraform -chdir=infra/terraform/environments/production output -raw ecs_cluster_name)" \
  --service "$(terraform -chdir=infra/terraform/environments/production output -raw ecs_web_service_name)" --force-new-deployment
aws ecs update-service --cluster "$(terraform -chdir=infra/terraform/environments/production output -raw ecs_cluster_name)" \
  --service "$(terraform -chdir=infra/terraform/environments/production output -raw ecs_worker_service_name)" --force-new-deployment
```

（この一連の流れは`.github/workflows/deploy.yml`（手動トリガー、`workflow_dispatch`）としてGitHub Actionsに下準備済み。実行にはリポジトリのSecrets/Variablesの設定と、GitHub用OIDCロールのAWS側での作成が別途必要。詳細はワークフローファイル冒頭のコメントを参照。）

### 5. 初回マイグレーション

Web/ワーカーサービスは起動時にマイグレーションを自動実行しない（複数タスクが同時に起動した際の競合を避けるため）。デプロイのたびに1回だけ手動、またはCI/CDから以下のように実行する。

```sh
aws ecs run-task \
  --cluster "$(terraform -chdir=infra/terraform/environments/production output -raw ecs_cluster_name)" \
  --task-definition "$(aws ecs describe-services --cluster ... --services ... --query 'services[0].taskDefinition' --output text)" \
  --overrides '{"containerOverrides":[{"name":"web","command":["php","artisan","migrate","--force"]}]}' \
  --launch-type FARGATE --network-configuration '...'
```

### 6. フロントエンドのデプロイ

```sh
cd frontend
npx ng build --configuration production

BUCKET=$(terraform -chdir=../infra/terraform/environments/production output -raw frontend_bucket_name)
DIST_ID=$(terraform -chdir=../infra/terraform/environments/production output -raw frontend_cloudfront_distribution_id)

aws s3 sync dist/frontend/browser "s3://$BUCKET" --delete
aws cloudfront create-invalidation --distribution-id "$DIST_ID" --paths "/*"
```

## 運用上の注意点

- **SESサンドボックス**: 新規AWSアカウントのSESはデフォルトで「サンドボックスモード」（検証済みメールアドレス宛にしか送信できない）。本番で任意の宛先に送るには、AWSサポートへ別途「Production access」を申請する必要がある（Terraformでは自動化できない）。
- **APP_KEY**: TerraformでSecrets Managerに1回だけランダム生成して保存する（`lifecycle.ignore_changes` により、以後のapplyで再生成されない）。ローテーションしたい場合は、Secrets Manager側の値を手動で更新すること（Terraform管理下で再生成すると、既存セッション・暗号化済みデータが復号できなくなる）。
- **APP_KEYの手動再生成が必要な場合**: `aws secretsmanager put-secret-value --secret-id tms-app-production/app-key --secret-string '{"APP_KEY":"base64:..."}'` のように直接更新し、Terraform側は関与させない。
- **RDSの削除保護**: `deletion_protection = true` がデフォルト。誤操作防止のため、実際に削除する場合は変数を明示的にfalseにしてapplyしてから削除する。
- **コスト**: NAT Gatewayは既定で1つ（`single_nat_gateway = true`）、RDSはシングルAZ（`db_multi_az = false`）、Fargate Web最小2タスクとしている。可用性を優先する場合は `terraform.tfvars` でこれらを調整する（コストは増える）。
- **教材ファイルの移行**: 既にローカル/開発環境で登録された研修教材ファイル（`storage/app/public/training-lessons/`）は自動移行されない。本番初期構築時にS3へ手動でアップロードするか、別途移行スクリプトを用意すること。

## このコードが対象外としているもの

- GitHub Actions用OIDCロールなど、CI/CDが実際にAWSへ接続するためのIAM設定（`.github/workflows/deploy.yml`はワークフロー定義のみ下準備済み。AWS側のロール作成は別途必要）
- ドメインの取得・Route53への登録
- SESの本番アクセス申請（AWSサポートへの手動申請）
- ステージング環境（`environments/production` のみ。増やす場合は `environments/staging` を同様の構成で追加する）
