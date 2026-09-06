# ECSタスク実行ロール（ECRからのpull・ログ出力・Secrets Manager読み取り）と、
# タスクロール（アプリ自身が実行時に使うAWS権限。S3・SES）を分離して定義する。

data "aws_iam_policy_document" "ecs_assume_role" {
  statement {
    actions = ["sts:AssumeRole"]

    principals {
      type        = "Service"
      identifiers = ["ecs-tasks.amazonaws.com"]
    }
  }
}

resource "aws_iam_role" "task_execution" {
  name               = "${var.name_prefix}-ecs-task-execution"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_role.json

  tags = var.tags
}

resource "aws_iam_role_policy_attachment" "task_execution_managed" {
  role       = aws_iam_role.task_execution.name
  policy_arn = "arn:aws:iam::aws:policy/service-role/AmazonECSTaskExecutionRolePolicy"
}

data "aws_iam_policy_document" "task_execution_secrets" {
  statement {
    actions   = ["secretsmanager:GetSecretValue"]
    resources = var.secret_arns_for_iam
  }
}

resource "aws_iam_role_policy" "task_execution_secrets" {
  name   = "${var.name_prefix}-secrets-read"
  role   = aws_iam_role.task_execution.id
  policy = data.aws_iam_policy_document.task_execution_secrets.json
}

resource "aws_iam_role" "task" {
  name               = "${var.name_prefix}-ecs-task"
  assume_role_policy = data.aws_iam_policy_document.ecs_assume_role.json

  tags = var.tags
}

data "aws_iam_policy_document" "task_app_permissions" {
  statement {
    sid = "AppStorageBucketAccess"
    actions = [
      "s3:GetObject",
      "s3:PutObject",
      "s3:DeleteObject",
    ]
    resources = ["${var.app_storage_bucket_arn}/*"]
  }

  statement {
    sid       = "AppStorageBucketList"
    actions   = ["s3:ListBucket"]
    resources = [var.app_storage_bucket_arn]
  }

  statement {
    sid       = "SendMailViaSes"
    actions   = ["ses:SendEmail", "ses:SendRawEmail"]
    resources = ["*"]
  }
}

resource "aws_iam_role_policy" "task_app_permissions" {
  name   = "${var.name_prefix}-app-permissions"
  role   = aws_iam_role.task.id
  policy = data.aws_iam_policy_document.task_app_permissions.json
}
