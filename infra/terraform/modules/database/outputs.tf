output "endpoint" {
  value = aws_db_instance.this.address
}

output "security_group_id" {
  value = aws_security_group.rds.id
}

output "secret_arn" {
  description = "ECSタスク定義のsecretsで参照するSecrets ManagerシークレットのARN。"
  value       = aws_secretsmanager_secret.db_credentials.arn
}
