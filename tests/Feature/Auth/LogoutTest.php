<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LogoutTest extends TestCase
{
    use RefreshDatabase;

    /** ログイン済みユーザーはログアウトできる */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->withSpaOrigin()->actingAs($user)->postJson('/api/logout');

        $response->assertNoContent();

        // /api/logout は auth:sanctum を通過する際にデフォルトガードが
        // 一時的に "sanctum" に切り替わるため、ログアウト対象である
        // "web" ガードを明示して検証する。
        $this->assertGuest('web');
    }

    /** 未ログインの状態でログアウトAPIを叩くと未認証エラーになる */
    public function test_guest_cannot_logout(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }
}
