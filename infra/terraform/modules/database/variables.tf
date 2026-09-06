variable "name_prefix" {
  type = string
}

variable "vpc_id" {
  type = string
}

variable "private_subnet_ids" {
  description = "RDSを配置するプライベートサブネットのID一覧（2つ以上）。"
  type        = list(string)
}

variable "allowed_security_group_ids" {
  description = "5432番ポートへの接続を許可するセキュリティグループ（ECSタスク用SG）のID一覧。"
  type        = list(string)
}

variable "engine_version" {
  description = "PostgreSQLのバージョン。開発環境（Sail）に合わせる。"
  type        = string
  default     = "18"
}

variable "instance_class" {
  type    = string
  default = "db.t4g.micro"
}

variable "allocated_storage" {
  description = "初期ストレージ容量（GB）。"
  type        = number
  default     = 20
}

variable "max_allocated_storage" {
  description = "オートスケーリングの上限ストレージ容量（GB）。"
  type        = number
  default     = 100
}

variable "multi_az" {
  description = "true にすると別AZにスタンバイを作成し、障害時に自動フェイルオーバーする（費用は約2倍）。"
  type        = bool
  default     = false
}

variable "backup_retention_days" {
  type    = number
  default = 7
}

variable "database_name" {
  type    = string
  default = "tms_app"
}

variable "master_username" {
  type    = string
  default = "tms_app_admin"
}

variable "deletion_protection" {
  description = "誤ってterraform destroy等で削除されるのを防ぐ。本番では原則true。"
  type        = bool
  default     = true
}

variable "skip_final_snapshot" {
  description = "削除時に最終スナップショットを取らずに済ませるか。本番ではfalse推奨。"
  type        = bool
  default     = false
}

variable "tags" {
  type    = map(string)
  default = {}
}
