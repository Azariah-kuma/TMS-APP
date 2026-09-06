variable "aws_region" {
  type    = string
  default = "ap-northeast-1"
}

variable "availability_zones" {
  type    = list(string)
  default = ["ap-northeast-1a", "ap-northeast-1c"]
}

# ---------------------------------------------------------------------------
# ドメイン（必須・デフォルトなし）
# ---------------------------------------------------------------------------

variable "hosted_zone_domain" {
  description = "既存のRoute53パブリックホストゾーンのドメイン名（例: example.com）。"
  type        = string
}

variable "api_domain_name" {
  description = "バックエンドAPIのFQDN（例: api.tms.example.com）。"
  type        = string
}

variable "app_domain_name" {
  description = "フロントエンド（Angular SPA）のFQDN（例: tms.example.com）。"
  type        = string
}

variable "storage_domain_name" {
  description = "研修教材ファイル配信用のFQDN（例: files.tms.example.com）。"
  type        = string
}

variable "mail_domain" {
  description = "SESで送信元として認証するドメイン（例: tms.example.com）。"
  type        = string
}

# ---------------------------------------------------------------------------
# S3バケット名（グローバル一意のため必須・デフォルトなし）
# ---------------------------------------------------------------------------

variable "app_storage_bucket_name" {
  description = "研修教材ファイル用S3バケット名（グローバル一意。例: tms-app-production-storage）。"
  type        = string
}

variable "frontend_bucket_name" {
  description = "フロントエンド配信用S3バケット名（グローバル一意。例: tms-app-production-frontend）。"
  type        = string
}

# ---------------------------------------------------------------------------
# アプリケーション設定
# ---------------------------------------------------------------------------

variable "app_name" {
  type    = string
  default = "TMS-APP"
}

variable "image_tag" {
  description = "デプロイするコンテナイメージのタグ（CI/CDから渡す想定）。"
  type        = string
  default     = "latest"
}

# ---------------------------------------------------------------------------
# サイジング・可用性（コストに直結するため明示的に変数化する）
# ---------------------------------------------------------------------------

variable "db_instance_class" {
  type    = string
  default = "db.t4g.micro"
}

variable "db_multi_az" {
  type    = bool
  default = false
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

variable "worker_desired_count" {
  type    = number
  default = 1
}

variable "single_nat_gateway" {
  description = "true でNAT Gatewayを1つに絞りコストを抑える（false でAZごとに作成し可用性を上げる）。"
  type        = bool
  default     = true
}

locals {
  name_prefix = "tms-app-production"

  common_tags = {
    Project     = "tms-app"
    Environment = "production"
    ManagedBy   = "terraform"
  }
}
