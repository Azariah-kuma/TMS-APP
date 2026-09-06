# Terraform状態管理用リソース（S3バケット＋DynamoDBロックテーブル）を作成するブートストラップ用ルートモジュール。
#
# このモジュールだけはリモートバックエンドを使わず、ローカルstateで実行する
# （state保存先そのものをこれから作るため、鶏と卵の関係になるのを避ける）。
#
# 使い方:
#   cd infra/terraform/bootstrap
#   terraform init
#   terraform apply
#
# 完了後、出力される bucket 名・dynamodb_table 名を
# environments/production/backend.tf に設定してから、
# environments/production 側で `terraform init` する。

terraform {
  required_version = ">= 1.9"

  required_providers {
    aws = {
      source  = "hashicorp/aws"
      version = "~> 5.0"
    }
  }
}

provider "aws" {
  region = var.aws_region

  default_tags {
    tags = {
      Project   = "tms-app"
      ManagedBy = "terraform"
      Layer     = "bootstrap"
    }
  }
}

resource "aws_s3_bucket" "terraform_state" {
  bucket = var.state_bucket_name

  # 誤operationでバケットごと削除されるのを防ぐ。本当に削除する場合は
  # 事前にこのフラグをfalseに変更してapplyし直す必要がある。
  lifecycle {
    prevent_destroy = true
  }
}

resource "aws_s3_bucket_versioning" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_s3_bucket_public_access_block" "terraform_state" {
  bucket = aws_s3_bucket.terraform_state.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_dynamodb_table" "terraform_lock" {
  name         = var.lock_table_name
  billing_mode = "PAY_PER_REQUEST"
  hash_key     = "LockID"

  attribute {
    name = "LockID"
    type = "S"
  }

  lifecycle {
    prevent_destroy = true
  }
}
