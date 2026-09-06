output "api_url" {
  value = "https://${var.api_domain_name}"
}

output "app_url" {
  value = "https://${var.app_domain_name}"
}

output "storage_url" {
  value = "https://${var.storage_domain_name}"
}

output "ecr_repository_url" {
  description = "CI/CDがLaravelアプリのイメージをpushする先。"
  value       = module.ecs.ecr_repository_url
}

output "ecs_cluster_name" {
  value = module.ecs.cluster_name
}

output "ecs_web_service_name" {
  value = module.ecs.web_service_name
}

output "ecs_worker_service_name" {
  value = module.ecs.worker_service_name
}

output "frontend_bucket_name" {
  description = "CI/CDが `ng build` の成果物をsyncする先。"
  value       = module.storage.frontend_bucket_name
}

output "frontend_cloudfront_distribution_id" {
  description = "フロントエンドデプロイ後のキャッシュ無効化（invalidation）に使う。"
  value       = module.storage.frontend_cloudfront_distribution_id
}

output "database_endpoint" {
  value = module.database.endpoint
}

output "private_subnet_ids" {
  description = "CI/CDから`aws ecs run-task`（マイグレーション実行等）する際のネットワーク設定に使う。"
  value       = module.network.private_subnet_ids
}

output "ecs_tasks_security_group_id" {
  description = "CI/CDから`aws ecs run-task`する際のネットワーク設定に使う。"
  value       = module.ecs.ecs_tasks_security_group_id
}
