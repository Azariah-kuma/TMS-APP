<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 退職済み（retired_at設定済み）の従業員を、研修登録や委任などの対象にしようとした場合に投げられる例外。
 */
final class EmployeeRetiredException extends UnprocessableDomainException {}
