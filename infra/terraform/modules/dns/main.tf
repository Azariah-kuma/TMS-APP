# Route53ゾーンの参照とACM証明書の発行のみを担当するモジュール
#（ALB用はデプロイ先リージョン、CloudFront用はus-east-1固定）。
#
# ALB/CloudFrontへのALIASレコード自体は、このモジュールの出力する証明書を使って
# ALB・CloudFrontを作った"後"でないと張れない（循環依存になるため）。そのため
# ALIASレコードの作成は呼び出し元（environments/production/main.tf）で行う。
#
# 前提: var.hosted_zone_domain のパブリックホストゾーンが既にRoute53に存在すること
# （ドメインの取得・登録・既存ゾーンからの移管は、このモジュールの対象外）。

terraform {
  required_providers {
    aws = {
      source                = "hashicorp/aws"
      version               = "~> 5.0"
      configuration_aliases = [aws.us_east_1]
    }
  }
}

data "aws_route53_zone" "this" {
  name         = var.hosted_zone_domain
  private_zone = false
}

# ---------------------------------------------------------------------------
# ALB（API）用証明書 ― デプロイ先リージョン
# ---------------------------------------------------------------------------

resource "aws_acm_certificate" "api" {
  domain_name       = var.api_domain_name
  validation_method = "DNS"

  lifecycle {
    create_before_destroy = true
  }

  tags = var.tags
}

resource "aws_route53_record" "api_cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.api.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  zone_id         = data.aws_route53_zone.this.zone_id
  name            = each.value.name
  type            = each.value.type
  records         = [each.value.record]
  ttl             = 60
  allow_overwrite = true
}

resource "aws_acm_certificate_validation" "api" {
  certificate_arn         = aws_acm_certificate.api.arn
  validation_record_fqdns = [for record in aws_route53_record.api_cert_validation : record.fqdn]
}

# ---------------------------------------------------------------------------
# CloudFront（フロントエンド・教材ファイル）用証明書 ― us-east-1固定
# ---------------------------------------------------------------------------

resource "aws_acm_certificate" "cloudfront" {
  provider = aws.us_east_1

  domain_name               = var.app_domain_name
  subject_alternative_names = [var.storage_domain_name]
  validation_method         = "DNS"

  lifecycle {
    create_before_destroy = true
  }

  tags = var.tags
}

resource "aws_route53_record" "cloudfront_cert_validation" {
  for_each = {
    for dvo in aws_acm_certificate.cloudfront.domain_validation_options : dvo.domain_name => {
      name   = dvo.resource_record_name
      record = dvo.resource_record_value
      type   = dvo.resource_record_type
    }
  }

  zone_id         = data.aws_route53_zone.this.zone_id
  name            = each.value.name
  type            = each.value.type
  records         = [each.value.record]
  ttl             = 60
  allow_overwrite = true
}

resource "aws_acm_certificate_validation" "cloudfront" {
  provider = aws.us_east_1

  certificate_arn         = aws_acm_certificate.cloudfront.arn
  validation_record_fqdns = [for record in aws_route53_record.cloudfront_cert_validation : record.fqdn]
}
