<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Exceptions\DepartmentInUseException;
use App\Models\Department;

/**
 * 使用中チェックのみのシンプルなガード句だが、Controllerを「リクエストを受けてレスポンスを
 * 返すだけ」に保つ方針（CLAUDE.md）を機械的に徹底するため、あえてActionに切り出している。
 */
final class DeleteDepartmentAction
{
    /** 従業員の配属履歴、または研修の閲覧対象部署として一度でも使われている部署は削除できない。 */
    public function execute(Department $department): void
    {
        if ($department->assignments()->exists()) {
            throw new DepartmentInUseException('この部署は従業員の配属履歴で使用されているため削除できません。');
        }

        if ($department->trainings()->exists()) {
            throw new DepartmentInUseException('この部署は研修の閲覧対象部署として使用されているため削除できません。');
        }

        $department->delete();
    }
}
