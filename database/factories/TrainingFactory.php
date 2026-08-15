<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Training;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 研修のファクトリクラス。
 */

/** @extends Factory<Training> */
class TrainingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->paragraph(),
            'category' => fake()->randomElement(['コンプライアンス', '技術研修', 'マネジメント', '情報セキュリティ']),
            'is_active' => true,
        ];
    }
}
