<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\EmployeeAssignment;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 従業員の割り当てのファクトリクラス。
 */

/** @extends Factory<EmployeeAssignment> */
class EmployeeAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'department_id' => Department::factory(),
            'position_id' => Position::factory(),
            'manager_id' => null,
            'started_at' => fake()->dateTimeBetween('-3 years', '-1 month'),
            'ended_at' => null,
        ];
    }

    /** 既に終了した過去の割り当てを表す状態。 */
    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => fake()->dateTimeBetween($attributes['started_at'], 'now'),
        ]);
    }
}
