<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /** 有効な情報を送信すると、ユーザーが作成されログイン状態になる */
    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->withSpaOrigin()->postJson('/api/register', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();
        $response->assertJsonFragment(['email' => 'taro@example.com']);

        $this->assertDatabaseHas('users', ['email' => 'taro@example.com']);
        $this->assertAuthenticated();
    }

    /** メールアドレスが既に使われている場合は登録できない */
    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->withSpaOrigin()->postJson('/api/register', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    /** パスワード確認が一致しない場合は登録できない */
    public function test_user_cannot_register_when_password_confirmation_does_not_match(): void
    {
        $response = $this->withSpaOrigin()->postJson('/api/register', [
            'name' => '山田太郎',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
    }
}
