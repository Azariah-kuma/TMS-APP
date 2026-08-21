<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 従業員の異動（部署・役職・上司の割り当て）が不正な場合に投げられる例外。
 * 開始日が現在の割り当てより前になっている、または指揮系統が循環する場合など。
 */
final class InvalidAssignmentPeriodException extends UnprocessableDomainException {}
