<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * すでに研修に受講登録済みの従業員が、再度受講登録しようとした場合に投げられる例外。
 */
final class AlreadyEnrolledException extends UnprocessableDomainException {}
