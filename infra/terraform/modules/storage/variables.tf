variable "name_prefix" {
  type = string
}

variable "app_storage_bucket_name" {
  description = "研修教材ファイル等（Laravelのpublicディスク）を保存するS3バケット名（グローバル一意）。"
  type        = string
}

variable "frontend_bucket_name" {
  description = "Angular SPAの静的ビルド成果物を配置するS3バケット名（グローバル一意）。"
  type        = string
}

variable "frontend_acm_certificate_arn" {
  description = "フロントエンドCloudFront用のACM証明書ARN（us-east-1リージョンで発行したもの。CloudFrontはus-east-1の証明書のみ対応）。カスタムドメインを使わない場合はnull。"
  type        = string
  default     = null
}

variable "app_storage_acm_certificate_arn" {
  description = "教材ファイル配信用CloudFront用のACM証明書ARN（us-east-1）。カスタムドメインを使わない場合はnull。"
  type        = string
  default     = null
}

variable "frontend_domain_names" {
  description = "フロントエンドCloudFrontに割り当てるカスタムドメイン一覧。使わない場合は空リスト。"
  type        = list(string)
  default     = []
}

variable "app_storage_domain_names" {
  description = "教材ファイル配信用CloudFrontに割り当てるカスタムドメイン一覧。使わない場合は空リスト。"
  type        = list(string)
  default     = []
}

variable "tags" {
  type    = map(string)
  default = {}
}
