output "ecr_repository_url" {
  value = aws_ecr_repository.app.repository_url
}

output "cluster_name" {
  value = aws_ecs_cluster.this.name
}

output "web_service_name" {
  value = aws_ecs_service.web.name
}

output "worker_service_name" {
  value = aws_ecs_service.worker.name
}

output "alb_dns_name" {
  value = aws_lb.this.dns_name
}

output "alb_zone_id" {
  description = "Route53のALIASレコード作成時に使う。"
  value       = aws_lb.this.zone_id
}

output "ecs_tasks_security_group_id" {
  value = aws_security_group.ecs_tasks.id
}
