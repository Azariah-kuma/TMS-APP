<?php

declare(strict_types=1);

namespace App\Enums;

/*
 * 監査ログの操作種別
 */
enum AuditLogAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
}
