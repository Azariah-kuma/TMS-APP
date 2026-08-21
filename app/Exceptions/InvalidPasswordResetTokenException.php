<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * パスワード設定・再設定のトークンが無効、または有効期限切れの場合に投げられる例外。
 */
final class InvalidPasswordResetTokenException extends UnprocessableDomainException {}
