output "state_bucket_name" {
  description = "environments/*/backend.tf に設定するS3バケット名。"
  value       = aws_s3_bucket.terraform_state.id
}

output "lock_table_name" {
  description = "environments/*/backend.tf に設定するDynamoDBテーブル名。"
  value       = aws_dynamodb_table.terraform_lock.name
}
