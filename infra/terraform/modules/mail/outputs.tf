output "domain_identity_arn" {
  value = aws_ses_domain_identity.this.arn
}

output "verified_domain" {
  value = aws_ses_domain_identity_verification.this.id
}
