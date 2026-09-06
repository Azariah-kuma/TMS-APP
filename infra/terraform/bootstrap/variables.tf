variable "aws_region" {
  description = "ブートストラップリソースを作成するAWSリージョン。"
  type        = string
  default     = "ap-northeast-1"
}

variable "state_bucket_name" {
  description = "Terraform state用S3バケット名（グローバルで一意である必要がある）。"
  type        = string
  default     = "tms-app-terraform-state"
}

variable "lock_table_name" {
  description = "Terraform stateのロック用DynamoDBテーブル名。"
  type        = string
  default     = "tms-app-terraform-locks"
}
