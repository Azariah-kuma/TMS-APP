# tms-app 本番環境のルートモジュール。
# 各モジュールを結線し、依存関係の都合上ここで直接作る必要があるリソース
# （APP_KEYシークレット、ALB/CloudFrontへのRoute53 ALIASレコード）もここに置く。

module "network" {
  source = "../../modules/network"

  name_prefix        = local.name_prefix
  availability_zones = var.availability_zones
  single_nat_gateway = var.single_nat_gateway
  tags               = local.common_tags
}

# ACM証明書の発行のみを担当（ALB/CloudFrontへのALIASレコードは循環依存を避けるため下部で作成）。
module "dns" {
  source = "../../modules/dns"

  providers = {
    aws           = aws
    aws.us_east_1 = aws.us_east_1
  }

  hosted_zone_domain  = var.hosted_zone_domain
  api_domain_name     = var.api_domain_name
  app_domain_name     = var.app_domain_name
  storage_domain_name = var.storage_domain_name
  tags                = local.common_tags
}

module "mail" {
  source = "../../modules/mail"

  aws_region         = var.aws_region
  hosted_zone_domain = var.hosted_zone_domain
  mail_domain        = var.mail_domain
  tags               = local.common_tags
}

module "storage" {
  source = "../../modules/storage"

  name_prefix             = local.name_prefix
  app_storage_bucket_name = var.app_storage_bucket_name
  frontend_bucket_name    = var.frontend_bucket_name

  frontend_domain_names           = [var.app_domain_name]
  app_storage_domain_names        = [var.storage_domain_name]
  frontend_acm_certificate_arn    = module.dns.cloudfront_certificate_arn
  app_storage_acm_certificate_arn = module.dns.cloudfront_certificate_arn

  tags = local.common_tags
}

module "database" {
  source = "../../modules/database"

  name_prefix        = local.name_prefix
  vpc_id             = module.network.vpc_id
  private_subnet_ids = module.network.private_subnet_ids
  instance_class     = var.db_instance_class
  multi_az           = var.db_multi_az

  # ECSタスクSGはこの後 module.ecs で作られるため、一旦空で作成し、
  # 下部の aws_vpc_security_group_ingress_rule で追加登録する。
  allowed_security_group_ids = []

  tags = local.common_tags
}

resource "random_id" "app_key" {
  byte_length = 32
}

resource "aws_secretsmanager_secret" "app_key" {
  name        = "${local.name_prefix}/app-key"
  description = "LaravelのAPP_KEY。値はTerraformでランダム生成し、以後変更しない（変更するとセッション・暗号化済みデータが復号不能になる）。"

  tags = local.common_tags
}

resource "aws_secretsmanager_secret_version" "app_key" {
  secret_id     = aws_secretsmanager_secret.app_key.id
  secret_string = jsonencode({ APP_KEY = "base64:${random_id.app_key.b64_std}" })

  lifecycle {
    ignore_changes = [secret_string]
  }
}

module "ecs" {
  source = "../../modules/ecs"

  name_prefix        = local.name_prefix
  aws_region         = var.aws_region
  vpc_id             = module.network.vpc_id
  public_subnet_ids  = module.network.public_subnet_ids
  private_subnet_ids = module.network.private_subnet_ids

  image_tag           = var.image_tag
  alb_certificate_arn = module.dns.api_certificate_arn

  web_desired_count    = var.web_desired_count
  web_min_count        = var.web_min_count
  web_max_count        = var.web_max_count
  worker_desired_count = var.worker_desired_count

  app_storage_bucket_arn = module.storage.app_storage_bucket_arn

  app_environment = {
    APP_NAME                    = var.app_name
    APP_ENV                     = "production"
    APP_DEBUG                   = "false"
    APP_URL                     = "https://${var.api_domain_name}"
    APP_LOCALE                  = "ja"
    APP_FALLBACK_LOCALE         = "ja"
    APP_FAKER_LOCALE            = "ja_JP"
    LOG_CHANNEL                 = "stack"
    LOG_LEVEL                   = "warning"
    SESSION_DRIVER              = "database"
    SESSION_LIFETIME            = "120"
    SESSION_SECURE_COOKIE       = "true"
    CACHE_STORE                 = "database"
    QUEUE_CONNECTION            = "database"
    FILESYSTEM_PUBLIC_DRIVER    = "s3"
    FILESYSTEM_PUBLIC_URL       = "https://${var.storage_domain_name}"
    AWS_DEFAULT_REGION          = var.aws_region
    AWS_BUCKET                  = module.storage.app_storage_bucket_name
    AWS_USE_PATH_STYLE_ENDPOINT = "false"
    MAIL_MAILER                 = "ses"
    MAIL_FROM_ADDRESS           = "no-reply@${var.mail_domain}"
    MAIL_FROM_NAME              = var.app_name
    FRONTEND_URL                = "https://${var.app_domain_name}"
    FRONTEND_URLS               = "https://${var.app_domain_name}"
    SANCTUM_STATEFUL_DOMAINS    = var.app_domain_name
  }

  app_secrets = {
    APP_KEY       = "${aws_secretsmanager_secret.app_key.arn}:APP_KEY::"
    DB_CONNECTION = "${module.database.secret_arn}:DB_CONNECTION::"
    DB_HOST       = "${module.database.secret_arn}:DB_HOST::"
    DB_PORT       = "${module.database.secret_arn}:DB_PORT::"
    DB_DATABASE   = "${module.database.secret_arn}:DB_DATABASE::"
    DB_USERNAME   = "${module.database.secret_arn}:DB_USERNAME::"
    DB_PASSWORD   = "${module.database.secret_arn}:DB_PASSWORD::"
  }

  secret_arns_for_iam = [
    aws_secretsmanager_secret.app_key.arn,
    module.database.secret_arn,
  ]

  tags = local.common_tags
}

# ECSタスクSGからRDSへの接続を許可する（database/ecsモジュール間の循環参照を避けるため、
# ここでモジュール外から追加登録する）。
resource "aws_vpc_security_group_ingress_rule" "rds_from_ecs" {
  security_group_id            = module.database.security_group_id
  referenced_security_group_id = module.ecs.ecs_tasks_security_group_id
  from_port                    = 5432
  to_port                      = 5432
  ip_protocol                  = "tcp"
  description                  = "ECSタスクからのPostgreSQL接続を許可"
}

# ---------------------------------------------------------------------------
# Route53 ALIASレコード（ALB・CloudFront。両モジュールの作成後にここで結線する）
# ---------------------------------------------------------------------------

resource "aws_route53_record" "api" {
  zone_id = module.dns.zone_id
  name    = var.api_domain_name
  type    = "A"

  alias {
    name                   = module.ecs.alb_dns_name
    zone_id                = module.ecs.alb_zone_id
    evaluate_target_health = true
  }
}

resource "aws_route53_record" "app" {
  zone_id = module.dns.zone_id
  name    = var.app_domain_name
  type    = "A"

  alias {
    name                   = module.storage.frontend_cloudfront_domain_name
    zone_id                = "Z2FDTNDATAQYW2" # CloudFrontの固定ホストゾーンID
    evaluate_target_health = false
  }
}

resource "aws_route53_record" "storage" {
  zone_id = module.dns.zone_id
  name    = var.storage_domain_name
  type    = "A"

  alias {
    name                   = module.storage.app_storage_cloudfront_domain_name
    zone_id                = "Z2FDTNDATAQYW2" # CloudFrontの固定ホストゾーンID
    evaluate_target_health = false
  }
}
