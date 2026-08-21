<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class LoginTest extends TestCase
{
    use RefreshDatabase;

    /** 正しい認証情報でログインできる */
    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSpaOrigin()->postJson('/api/login', [
            'email' => 'taro@example.com',
            'password' => 'password123',
        ]);

        $response->assertOk();
        $response->assertJsonFragment(['email' => $user->email]);
        $this->assertAuthenticatedAs($user);
    }

    /** ログイン直後のレスポンスにも、部署・役職・is_managerが含まれる */
    public function test_login_response_includes_department_position_and_manager_status(): void
    {
        $manager = createEmployeeWithAssignment();
        createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);
        $manager->user->update(['password' => Hash::make('password123')]);

        $response = $this->withSpaOrigin()->postJson('/api/login', [
            'email' => $manager->user->email,
            'password' => 'password123',
        ]);

        $response->assertOk()
            ->assertJsonPath('employee.current_assignment.department_name', $manager->currentAssignment->department->name)
            ->assertJsonPath('employee.current_assignment.position_name', $manager->currentAssignment->position->name)
            ->assertJsonPath('employee.is_manager', true);
    }

    /** パスワードが誤っている場合はログインできない */
    public function test_user_cannot_login_with_incorrect_password(): void
    {
        User::factory()->create([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->withSpaOrigin()->postJson('/api/login', [
            'email' => 'taro@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
        $this->assertGuest();
    }

    /** 存在しないメールアドレスでも、パスワード誤りと同じエラーになる（ユーザー列挙対策） */
    public function test_user_cannot_login_with_unknown_email(): void
    {
        $response = $this->withSpaOrigin()->postJson('/api/login', [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
        $this->assertGuest();
    }

    /** 短時間に繰り返しログインに失敗すると、それ以上の試行はレート制限される */
    public function test_login_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'taro@example.com',
            'password' => Hash::make('password123'),
        ]);

        for ($i = 0; $i < 6; $i++) {
            $this->withSpaOrigin()->postJson('/api/login', [
                'email' => 'taro@example.com',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->withSpaOrigin()->postJson('/api/login', [
            'email' => 'taro@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }
}
