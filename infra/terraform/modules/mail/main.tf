# SESでのドメイン送信認証（DKIM）とカスタムMAIL FROMドメインを設定する。
#
# アプリ側は MAIL_MAILER=ses、IAMタスクロールに付与済みの ses:SendEmail 権限で送信する
# （SMTPクレデンシャルは発行せず、AWS SDK経由のAPI送信に統一している）。
#
# 注意: SESが新規AWSアカウントではデフォルトで「サンドボックスモード」（検証済みアドレス
# 宛にしか送信できない）になっている。本番で任意の宛先に送るには、AWSサポートへ
# サンドボックス解除（Production access）をこのTerraform適用とは別途申請する必要がある。

data "aws_route53_zone" "this" {
  name         = var.hosted_zone_domain
  private_zone = false
}

resource "aws_ses_domain_identity" "this" {
  domain = var.mail_domain
}

resource "aws_route53_record" "ses_verification" {
  zone_id = data.aws_route53_zone.this.zone_id
  name    = "_amazonses.${var.mail_domain}"
  type    = "TXT"
  ttl     = 600
  records = [aws_ses_domain_identity.this.verification_token]
}

resource "aws_ses_domain_identity_verification" "this" {
  domain = aws_ses_domain_identity.this.id

  depends_on = [aws_route53_record.ses_verification]
}

resource "aws_ses_domain_dkim" "this" {
  domain = aws_ses_domain_identity.this.domain
}

resource "aws_route53_record" "dkim" {
  count = 3

  zone_id = data.aws_route53_zone.this.zone_id
  name    = "${aws_ses_domain_dkim.this.dkim_tokens[count.index]}._domainkey.${var.mail_domain}"
  type    = "CNAME"
  ttl     = 600
  records = ["${aws_ses_domain_dkim.this.dkim_tokens[count.index]}.dkim.amazonses.com"]
}

resource "aws_ses_domain_mail_from" "this" {
  domain           = aws_ses_domain_identity.this.domain
  mail_from_domain = "${var.mail_from_subdomain}.${var.mail_domain}"
}

resource "aws_route53_record" "mail_from_mx" {
  zone_id = data.aws_route53_zone.this.zone_id
  name    = aws_ses_domain_mail_from.this.mail_from_domain
  type    = "MX"
  ttl     = 600
  records = ["10 feedback-smtp.${var.aws_region}.amazonses.com"]
}

resource "aws_route53_record" "mail_from_spf" {
  zone_id = data.aws_route53_zone.this.zone_id
  name    = aws_ses_domain_mail_from.this.mail_from_domain
  type    = "TXT"
  ttl     = 600
  records = ["v=spf1 include:amazonses.com -all"]
}
