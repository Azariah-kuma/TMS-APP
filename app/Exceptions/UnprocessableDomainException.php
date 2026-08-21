<?php

declare(strict_types=1);

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ドメインルール違反を表す例外の共通基底クラス。
 *
 * このアプリのドメイン例外は、いずれも「メッセージを添えて422 JSONで返す」という
 * 同一のrender()実装を持つ。各例外クラスでの重複を避けるため、ここに一元化する。
 */
abstract class UnprocessableDomainException extends DomainException
{
    public function render(Request $request): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], 422);
    }
}
