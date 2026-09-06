# キューワーカー（`php artisan queue:work`）のタスク定義・サービス。
# QUEUE_CONNECTION=database のため、常駐プロセスとして起動し続ける（Fargate常時起動）。
# ALBには紐付かない（ロードバランサ配下ではない）。

resource "aws_ecs_task_definition" "worker" {
  family                   = "${var.name_prefix}-worker"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = var.worker_cpu
  memory                   = var.worker_memory
  execution_role_arn       = aws_iam_role.task_execution.arn
  task_role_arn            = aws_iam_role.task.arn

  container_definitions = jsonencode([
    {
      name      = "worker"
      image     = local.image
      essential = true

      # web用イメージと同じイメージを使い回し、起動コマンドだけ差し替える
      # （Dockerfile側のENTRYPOINTがphp-fpm/nginx起動用のため、CMDでqueue:workに上書きする）。
      command = ["php", "artisan", "queue:work", "--sleep=3", "--tries=3", "--max-time=3600"]

      environment = local.container_environment
      secrets     = local.container_secrets

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.worker.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "worker"
        }
      }
    }
  ])

  tags = var.tags
}

resource "aws_ecs_service" "worker" {
  name            = "${var.name_prefix}-worker"
  cluster         = aws_ecs_cluster.this.id
  task_definition = aws_ecs_task_definition.worker.arn
  desired_count   = var.worker_desired_count
  launch_type     = "FARGATE"

  network_configuration {
    subnets         = var.private_subnet_ids
    security_groups = [aws_security_group.ecs_tasks.id]
  }

  lifecycle {
    ignore_changes = [task_definition, desired_count]
  }

  tags = var.tags
}
