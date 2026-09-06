output "app_storage_bucket_name" {
  value = aws_s3_bucket.app_storage.id
}

output "app_storage_bucket_arn" {
  value = aws_s3_bucket.app_storage.arn
}

output "app_storage_cloudfront_domain_name" {
  value = aws_cloudfront_distribution.app_storage.domain_name
}

output "frontend_bucket_name" {
  value = aws_s3_bucket.frontend.id
}

output "frontend_cloudfront_distribution_id" {
  description = "デプロイ時のCloudFrontキャッシュ無効化（invalidation）に使う。"
  value       = aws_cloudfront_distribution.frontend.id
}

output "frontend_cloudfront_domain_name" {
  value = aws_cloudfront_distribution.frontend.domain_name
}
