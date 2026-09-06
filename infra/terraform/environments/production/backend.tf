# リモートstateの設定。
#
# bootstrap（infra/terraform/bootstrap）を先にapplyし、そこで作成されたS3バケット名・
# DynamoDBテーブル名を下記の bucket / dynamodb_table に設定してから
# `terraform init` すること。backend ブロックには変数を使えないため、直接値を書く。
terraform {
  backend "s3" {
    bucket         = "tms-app-terraform-state"
    key            = "production/terraform.tfstate"
    region         = "ap-northeast-1"
    dynamodb_table = "tms-app-terraform-locks"
    encrypt        = true
  }
}
