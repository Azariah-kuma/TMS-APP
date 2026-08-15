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
}
