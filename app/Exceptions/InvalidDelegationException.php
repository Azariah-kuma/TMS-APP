<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 委任の内容が不正な場合に投げられる例外。
 * 自分自身への委任、開始日より前の終了日、既存委任との期間重複など。
 */
final class InvalidDelegationException extends UnprocessableDomainException {}
