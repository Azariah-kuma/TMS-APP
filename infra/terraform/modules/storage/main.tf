# S3 + CloudFront (Origin Access Control) で以下2系統を配信するモジュール。
#   1. app_storage: 研修教材ファイル等（LaravelのpublicディスクをS3に向ける）
#   2. frontend   : Angular SPAの静的ビルド成果物（SPAルーティングのため403/404をindex.htmlへフォールバック）
#
# いずれもS3バケット自体は非公開のままとし、CloudFront経由でのみ読み出せるようにする。

# ---------------------------------------------------------------------------
# 研修教材ファイル用バケット
# ---------------------------------------------------------------------------

resource "aws_s3_bucket" "app_storage" {
  bucket = var.app_storage_bucket_name

  tags = merge(var.tags, {
    Name    = var.app_storage_bucket_name
    Purpose = "app-storage"
  })
}

resource "aws_s3_bucket_public_access_block" "app_storage" {
  bucket = aws_s3_bucket.app_storage.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_versioning" "app_storage" {
  bucket = aws_s3_bucket.app_storage.id

  versioning_configuration {
    status = "Enabled"
  }
}

resource "aws_s3_bucket_server_side_encryption_configuration" "app_storage" {
  bucket = aws_s3_bucket.app_storage.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

# CORS: フロントエンド（Angular SPA）からのアップロード後の直接参照等を想定し、GETのみ許可する。
resource "aws_s3_bucket_cors_configuration" "app_storage" {
  bucket = aws_s3_bucket.app_storage.id

  cors_rule {
    allowed_methods = ["GET"]
    allowed_origins = ["*"]
    allowed_headers = ["*"]
    max_age_seconds = 3000
  }
}

resource "aws_cloudfront_origin_access_control" "app_storage" {
  name                              = "${var.name_prefix}-app-storage-oac"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_cloudfront_distribution" "app_storage" {
  enabled         = true
  comment         = "${var.name_prefix} training lesson attachments"
  price_class     = "PriceClass_200" # 北米・欧州・アジアをカバー（日本含む）。全世界配信よりコストを抑える。
  aliases         = var.app_storage_domain_names
  is_ipv6_enabled = true

  origin {
    domain_name              = aws_s3_bucket.app_storage.bucket_regional_domain_name
    origin_id                = "app-storage-s3"
    origin_access_control_id = aws_cloudfront_origin_access_control.app_storage.id
  }

  default_cache_behavior {
    allowed_methods        = ["GET", "HEAD"]
    cached_methods         = ["GET", "HEAD"]
    target_origin_id       = "app-storage-s3"
    viewer_protocol_policy = "redirect-to-https"
    cache_policy_id        = "658327ea-f89d-4fab-a63d-7e88639e58f6" # AWS管理ポリシー: CachingOptimized
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    cloudfront_default_certificate = var.app_storage_acm_certificate_arn == null
    acm_certificate_arn            = var.app_storage_acm_certificate_arn
    ssl_support_method             = var.app_storage_acm_certificate_arn == null ? null : "sni-only"
    minimum_protocol_version       = var.app_storage_acm_certificate_arn == null ? null : "TLSv1.2_2021"
  }

  tags = var.tags
}

data "aws_iam_policy_document" "app_storage_cloudfront_read" {
  statement {
    sid       = "AllowCloudFrontServicePrincipalReadOnly"
    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.app_storage.arn}/*"]

    principals {
      type        = "Service"
      identifiers = ["cloudfront.amazonaws.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "AWS:SourceArn"
      values   = [aws_cloudfront_distribution.app_storage.arn]
    }
  }
}

resource "aws_s3_bucket_policy" "app_storage" {
  bucket = aws_s3_bucket.app_storage.id
  policy = data.aws_iam_policy_document.app_storage_cloudfront_read.json
}

# ---------------------------------------------------------------------------
# フロントエンド（Angular SPA）配信用バケット
# ---------------------------------------------------------------------------

resource "aws_s3_bucket" "frontend" {
  bucket = var.frontend_bucket_name

  tags = merge(var.tags, {
    Name    = var.frontend_bucket_name
    Purpose = "frontend-spa"
  })
}

resource "aws_s3_bucket_public_access_block" "frontend" {
  bucket = aws_s3_bucket.frontend.id

  block_public_acls       = true
  block_public_policy     = true
  ignore_public_acls      = true
  restrict_public_buckets = true
}

resource "aws_s3_bucket_server_side_encryption_configuration" "frontend" {
  bucket = aws_s3_bucket.frontend.id

  rule {
    apply_server_side_encryption_by_default {
      sse_algorithm = "AES256"
    }
  }
}

resource "aws_cloudfront_origin_access_control" "frontend" {
  name                              = "${var.name_prefix}-frontend-oac"
  origin_access_control_origin_type = "s3"
  signing_behavior                  = "always"
  signing_protocol                  = "sigv4"
}

resource "aws_cloudfront_distribution" "frontend" {
  enabled             = true
  comment             = "${var.name_prefix} Angular SPA"
  price_class         = "PriceClass_200"
  aliases             = var.frontend_domain_names
  is_ipv6_enabled     = true
  default_root_object = "index.html"

  origin {
    domain_name              = aws_s3_bucket.frontend.bucket_regional_domain_name
    origin_id                = "frontend-s3"
    origin_access_control_id = aws_cloudfront_origin_access_control.frontend.id
  }

  default_cache_behavior {
    allowed_methods        = ["GET", "HEAD"]
    cached_methods         = ["GET", "HEAD"]
    target_origin_id       = "frontend-s3"
    viewer_protocol_policy = "redirect-to-https"
    cache_policy_id        = "658327ea-f89d-4fab-a63d-7e88639e58f6" # AWS管理ポリシー: CachingOptimized
  }

  # Angular Router（HTML5 history mode）は存在しないパスにアクセスされるため、
  # S3が返す403/404をindex.htmlにフォールバックさせ、Angular側でルーティングさせる。
  custom_error_response {
    error_code         = 403
    response_code      = 200
    response_page_path = "/index.html"
  }

  custom_error_response {
    error_code         = 404
    response_code      = 200
    response_page_path = "/index.html"
  }

  restrictions {
    geo_restriction {
      restriction_type = "none"
    }
  }

  viewer_certificate {
    cloudfront_default_certificate = var.frontend_acm_certificate_arn == null
    acm_certificate_arn            = var.frontend_acm_certificate_arn
    ssl_support_method             = var.frontend_acm_certificate_arn == null ? null : "sni-only"
    minimum_protocol_version       = var.frontend_acm_certificate_arn == null ? null : "TLSv1.2_2021"
  }

  tags = var.tags
}

data "aws_iam_policy_document" "frontend_cloudfront_read" {
  statement {
    sid       = "AllowCloudFrontServicePrincipalReadOnly"
    actions   = ["s3:GetObject"]
    resources = ["${aws_s3_bucket.frontend.arn}/*"]

    principals {
      type        = "Service"
      identifiers = ["cloudfront.amazonaws.com"]
    }

    condition {
      test     = "StringEquals"
      variable = "AWS:SourceArn"
      values   = [aws_cloudfront_distribution.frontend.arn]
    }
  }
}

resource "aws_s3_bucket_policy" "frontend" {
  bucket = aws_s3_bucket.frontend.id
  policy = data.aws_iam_policy_document.frontend_cloudfront_read.json
}
