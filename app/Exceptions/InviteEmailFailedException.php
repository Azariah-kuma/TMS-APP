<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 招待メールの（再）送信自体に失敗した場合に投げられる例外。
 */
final class InviteEmailFailedException extends UnprocessableDomainException {}
