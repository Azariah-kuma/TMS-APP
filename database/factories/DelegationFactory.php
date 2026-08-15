<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Delegation;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 委任のファクトリクラス。
 */

/** @extends Factory<Delegation> */
class DelegationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'delegator_id' => Employee::factory(),
            'delegate_id' => Employee::factory(),
            'started_at' => fake()->dateTimeBetween('-1 month', 'now'),
            'ended_at' => null,
        ];
    }
}
