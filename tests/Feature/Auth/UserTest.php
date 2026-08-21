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

    /** 部署・役職・is_managerが、ログイン直後だけでなくリロード後の取得でも欠けない */
    public function test_profile_includes_department_position_and_manager_status(): void
    {
        $manager = createEmployeeWithAssignment();
        createEmployeeWithAssignment(assignmentAttributes: ['manager_id' => $manager->id]);

        $response = $this->actingAs($manager->user)->getJson('/api/user');

        $response->assertOk()
            ->assertJsonPath('employee.current_assignment.department_name', $manager->currentAssignment->department->name)
            ->assertJsonPath('employee.current_assignment.position_name', $manager->currentAssignment->position->name)
            ->assertJsonPath('employee.is_manager', true);
    }
}
