<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmployeeRole;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 従業員のファクトリクラス。
 */

/** @extends Factory<Employee> */
class EmployeeFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'employee_code' => strtoupper(fake()->unique()->bothify('EMP-####')),
            'role' => EmployeeRole::Employee,
            'hired_at' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'retired_at' => null,
        ];
    }

    public function hr(): static
    {
        return $this->state(fn (array $attributes) => ['role' => EmployeeRole::Hr]);
    }
}
