<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/*
 * 研修Lessonに基づく進捗が不正な場合に投げられる例外。
 */
final class LessonBasedProgressException extends DomainException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
