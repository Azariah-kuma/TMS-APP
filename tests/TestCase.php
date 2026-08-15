<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * SanctumのSPA認証はRefererがsanctum.stateful設定に一致するリクエストのみ
     * セッションを開始する。テストでもフロントエンド（Angular）からのリクエストを
     * 再現するため、このヘッダーを付与する。
     */
    protected function withSpaOrigin(): static
    {
        return $this->withHeader('Referer', config('app.frontend_url', 'http://localhost:4200'));
    }
}
