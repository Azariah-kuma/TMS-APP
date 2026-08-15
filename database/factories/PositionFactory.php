<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 役職のファクトリクラス。
 */

/** @extends Factory<Position> */
class PositionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->jobTitle(),
            'code' => strtoupper(fake()->unique()->lexify('POS-???')),
            'rank' => fake()->numberBetween(1, 10),
        ];
    }
}
