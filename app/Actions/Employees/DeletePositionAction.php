<?php

declare(strict_types=1);

namespace App\Actions\Employees;

use App\Exceptions\PositionInUseException;
use App\Models\Position;

/**
 * 使用中チェックのみのシンプルなガード句だが、Controllerを「リクエストを受けてレスポンスを
 * 返すだけ」に保つ方針（CLAUDE.md）を機械的に徹底するため、あえてActionに切り出している。
 */
final class DeletePositionAction
{
    /** 従業員の配属履歴で一度でも使われている役職は削除できない。 */
    public function execute(Position $position): void
    {
        if ($position->assignments()->exists()) {
            throw new PositionInUseException('この役職は従業員の配属履歴で使用されているため削除できません。');
        }

        $position->delete();
    }
}
