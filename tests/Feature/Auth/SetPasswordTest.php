<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

final class SetPasswordTest extends TestCase
{
    use RefreshDatabase;

    /** 招待メールのリンク（有効なトークン）から、初回パスワードを設定してログイン状態になる */
    public function test_user_can_set_their_password_with_a_valid_token(): void
    {
        $user = User::factory()->create(['email' => 'taro@example.com']);
        $token = Password::createToken($user);

        $response = $this->withSpaOrigin()->postJson('/api/set-password', [
            'token' => $token,
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['email' => 'taro@example.com']);
        $this->assertAuthenticatedAs($user);
        expect(Hash::check('password123', $user->fresh()->password))->toBeTrue();
    }

    /** パスワード設定直後のレスポンスにも、部署・役職・is_managerが含まれる */
    public function test_set_password_response_includes_department_position_and_manager_status(): void
    {
        $manager = createEmployeeWithAssignment();
        createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
        $token = Password::createToken($manager->user);

        $response = $this->withSpaOrigin()->postJson('/api/set-password', [
            'token' => $token,
            'email' => $manager->user->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('employee.current_assignment.department_name', $manager->currentAssignment->department->name)
            ->assertJsonPath('employee.is_manager', true);
    }

    /** トークンが無効な場合は設定できない */
    public function test_user_cannot_set_their_password_with_an_invalid_token(): void
    {
        User::factory()->create(['email' => 'taro@example.com']);

        $response = $this->withSpaOrigin()->postJson('/api/set-password', [
            'token' => 'invalid-token',
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertUnprocessable();
        $this->assertGuest();
    }

    /** パスワード確認が一致しない場合は設定できない */
    public function test_user_cannot_set_their_password_when_confirmation_does_not_match(): void
    {
        $user = User::factory()->create(['email' => 'taro@example.com']);
        $token = Password::createToken($user);

        $response = $this->withSpaOrigin()->postJson('/api/set-password', [
            'token' => $token,
            'email' => 'taro@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['password']);
        $this->assertGuest();
    }
}
