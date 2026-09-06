variable "hosted_zone_domain" {
  description = "既存のRoute53パブリックホストゾーンのドメイン名（例: example.com）。事前に取得・登録済みであることが前提。"
  type        = string
}

variable "api_domain_name" {
  description = "バックエンドAPI（ALB）に割り当てるFQDN（例: api.tms.example.com）。"
  type        = string
}

variable "app_domain_name" {
  description = "フロントエンド（CloudFront）に割り当てるFQDN（例: tms.example.com）。"
  type        = string
}

variable "storage_domain_name" {
  description = "研修教材ファイル配信用CloudFrontに割り当てるFQDN（例: files.tms.example.com）。"
  type        = string
}

variable "tags" {
  type    = map(string)
  default = {}
}
