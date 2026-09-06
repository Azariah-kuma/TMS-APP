# RDS PostgreSQL（本体） + 接続情報を保管するSecrets Managerシークレットを作成するモジュール。
#
# マスターパスワードはTerraform側で乱数生成し、Secrets Managerにのみ保存する
# （tfstateにも平文で残る点は運用上注意。state自体はS3+SSE+バケット非公開で保護している）。

resource "random_password" "master" {
  length  = 32
  special = false # RDSのパスワードに使えない記号を避けるため、英数字のみにする
}

resource "aws_db_subnet_group" "this" {
  name       = "${var.name_prefix}-db"
  subnet_ids = var.private_subnet_ids

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-db-subnet-group"
  })
}

resource "aws_security_group" "rds" {
  name_prefix = "${var.name_prefix}-rds-"
  description = "RDS PostgreSQLへのアクセス許可（ECSタスクSGからの5432番のみ）。"
  vpc_id      = var.vpc_id

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-rds-sg"
  })

  lifecycle {
    create_before_destroy = true
  }
}

resource "aws_vpc_security_group_ingress_rule" "from_app" {
  for_each = toset(var.allowed_security_group_ids)

  security_group_id            = aws_security_group.rds.id
  referenced_security_group_id = each.value
  from_port                    = 5432
  to_port                      = 5432
  ip_protocol                  = "tcp"
  description                  = "ECSタスクからのPostgreSQL接続を許可"
}

resource "aws_vpc_security_group_egress_rule" "all" {
  security_group_id = aws_security_group.rds.id
  cidr_ipv4         = "0.0.0.0/0"
  ip_protocol       = "-1"
}

resource "aws_db_instance" "this" {
  identifier     = "${var.name_prefix}-postgres"
  engine         = "postgres"
  engine_version = var.engine_version

  instance_class        = var.instance_class
  allocated_storage     = var.allocated_storage
  max_allocated_storage = var.max_allocated_storage
  storage_type          = "gp3"
  storage_encrypted     = true

  db_name  = var.database_name
  username = var.master_username
  password = random_password.master.result
  port     = 5432

  db_subnet_group_name   = aws_db_subnet_group.this.name
  vpc_security_group_ids = [aws_security_group.rds.id]
  publicly_accessible    = false

  multi_az                = var.multi_az
  backup_retention_period = var.backup_retention_days
  backup_window           = "17:00-17:30"         # UTC 17:00 = JST 02:00（深夜帯）
  maintenance_window      = "sun:18:00-sun:19:00" # UTC 日曜18:00 = JST 月曜03:00

  deletion_protection       = var.deletion_protection
  skip_final_snapshot       = var.skip_final_snapshot
  final_snapshot_identifier = var.skip_final_snapshot ? null : "${var.name_prefix}-postgres-final"

  copy_tags_to_snapshot = true

  tags = merge(var.tags, {
    Name = "${var.name_prefix}-postgres"
  })
}

resource "aws_secretsmanager_secret" "db_credentials" {
  name        = "${var.name_prefix}/database"
  description = "RDS PostgreSQLの接続情報（Laravelアプリのタスク定義からsecretsとして参照する）。"

  tags = var.tags
}

resource "aws_secretsmanager_secret_version" "db_credentials" {
  secret_id = aws_secretsmanager_secret.db_credentials.id

  secret_string = jsonencode({
    DB_CONNECTION = "pgsql"
    DB_HOST       = aws_db_instance.this.address
    DB_PORT       = tostring(aws_db_instance.this.port)
    DB_DATABASE   = var.database_name
    DB_USERNAME   = var.master_username
    DB_PASSWORD   = random_password.master.result
  })
}
