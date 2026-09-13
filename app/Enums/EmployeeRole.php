<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 従業員のシステム上のロール。
 * なお、上司かどうかはEmployeeAssignment.manager_id によって導出する。
 */
enum EmployeeRole: string
{
    case Employee = 'employee';
    case Hr = 'hr';
    /** 全データの閲覧のみ許可される監査ロール（作成・更新・削除は不可）。 */
    case Audit = 'audit';
}
