output "zone_id" {
  description = "呼び出し元でALIASレコードを作成する際に使うRoute53ホストゾーンID。"
  value       = data.aws_route53_zone.this.zone_id
}

output "api_certificate_arn" {
  description = "ALBのHTTPSリスナーに設定する証明書ARN（デプロイ先リージョン）。"
  value       = aws_acm_certificate_validation.api.certificate_arn
}

output "cloudfront_certificate_arn" {
  description = "CloudFrontディストリビューションに設定する証明書ARN（us-east-1）。"
  value       = aws_acm_certificate_validation.cloudfront.certificate_arn
}
