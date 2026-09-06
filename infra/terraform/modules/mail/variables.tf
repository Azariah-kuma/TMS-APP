variable "aws_region" {
  description = "SESのMAIL FROMドメインのMXレコード生成に使うリージョン名。"
  type        = string
}

variable "hosted_zone_domain" {
  description = "既存のRoute53パブリックホストゾーンのドメイン名。"
  type        = string
}

variable "mail_domain" {
  description = "SESで送信元として認証するドメイン（例: tms.example.com）。招待メール等の送信元アドレスに使う。"
  type        = string
}

variable "mail_from_subdomain" {
  description = "カスタムMAIL FROMドメインのサブドメイン部分。到達率向上のため独自のMAIL FROMドメインを設定する（例: 'mail' → mail.tms.example.com）。"
  type        = string
  default     = "mail"
}

variable "tags" {
  type    = map(string)
  default = {}
}
