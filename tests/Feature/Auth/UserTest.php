<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UserTest extends TestCase
{
    use RefreshDatabase;

    /** ログイン済みユーザーは自分自身の情報を取得できる */
    public function test_authenticated_user_can_fetch_own_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertOk();
        $response->assertJsonFragment(['id' => $user->id, 'email' => $user->email]);
    }

    /** 未ログインの場合は取得できない */
    public function test_guest_cannot_fetch_user_profile(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized();
    }
}
