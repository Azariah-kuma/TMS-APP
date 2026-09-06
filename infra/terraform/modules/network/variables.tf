variable "name_prefix" {
  description = "リソース名のプレフィックス（例: tms-app-production）。"
  type        = string
}

variable "vpc_cidr" {
  description = "VPCのCIDRブロック。"
  type        = string
  default     = "10.0.0.0/16"
}

variable "availability_zones" {
  description = "使用するアベイラビリティゾーン（2つ以上を推奨。ALB・RDSのマルチAZ配置に必要）。"
  type        = list(string)
}

variable "single_nat_gateway" {
  description = "true の場合、NAT GatewayをAZ数分ではなく1つだけ作成してコストを抑える（可用性より費用を優先する場合）。"
  type        = bool
  default     = true
}

variable "tags" {
  description = "全リソースに付与する追加タグ。"
  type        = map(string)
  default     = {}
}
