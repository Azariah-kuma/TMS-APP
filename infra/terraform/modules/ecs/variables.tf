variable "name_prefix" {
  type = string
}

variable "aws_region" {
  type = string
}

variable "vpc_id" {
  type = string
}

variable "public_subnet_ids" {
  description = "ALBを配置するパブリックサブネット。"
  type        = list(string)
}

variable "private_subnet_ids" {
  description = "ECSタスク（Fargate）を配置するプライベートサブネット。"
  type        = list(string)
}

variable "rds_security_group_id" {
  description = "ECSタスクからのDB接続を許可するため、RDS側SGにこのモジュールのタスクSGを追加登録できるよう、参照用に受け取る（このモジュール自身はRDS SGを変更しない）。"
  type        = string
  default     = null
}

variable "image_tag" {
  description = "デプロイするコンテナイメージのタグ。CI/CDから更新される想定（初回applyは 'initial' 等のプレースホルダでも可。ECRに該当タグが無いとサービスは起動しない点に注意）。"
  type        = string
  default     = "latest"
}

variable "alb_certificate_arn" {
  description = "ALBのHTTPSリスナー用ACM証明書ARN（ALBと同じリージョンのもの）。nullの場合はHTTPのみで公開する（動作確認用途）。"
  type        = string
  default     = null
}

variable "web_cpu" {
  type    = number
  default = 512
}

variable "web_memory" {
  type    = number
  default = 1024
}

variable "web_desired_count" {
  type    = number
  default = 2
}

variable "web_min_count" {
  type    = number
  default = 2
}

variable "web_max_count" {
  type    = number
  default = 6
}

variable "worker_cpu" {
  type    = number
  default = 256
}

variable "worker_memory" {
  type    = number
  default = 512
}

variable "worker_desired_count" {
  type    = number
  default = 1
}

variable "log_retention_days" {
  type    = number
  default = 30
}

variable "app_environment" {
  description = "コンテナに渡す非機密の環境変数。"
  type        = map(string)
}

variable "app_secrets" {
  description = <<-EOT
    コンテナにSecrets Manager経由で渡す機密環境変数。
    key = コンテナ内の環境変数名, value = Secrets ManagerのシークレットARN（JSONキーを含む場合は "arn:...:secret:xxx:JSON_KEY::" 形式）。
  EOT
  type        = map(string)
}

variable "secret_arns_for_iam" {
  description = "タスク実行ロールにGetSecretValueを許可する対象のシークレットARN一覧（app_secretsの値の元ARN群）。"
  type        = list(string)
}

variable "app_storage_bucket_arn" {
  description = "アプリのIAMタスクロールに読み書きを許可するS3バケット（研修教材ファイル用）のARN。"
  type        = string
}

variable "tags" {
  type    = map(string)
  default = {}
}
