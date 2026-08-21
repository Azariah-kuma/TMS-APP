<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/*
 * ユーザーのファクトリクラス。
 */

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /** [姓, 姓のふりがな] の候補一覧。 */
    private const array LAST_NAMES = [
        ['山田', 'ヤマダ'],
        ['佐藤', 'サトウ'],
        ['鈴木', 'スズキ'],
        ['高橋', 'タカハシ'],
        ['田中', 'タナカ'],
        ['伊藤', 'イトウ'],
        ['渡辺', 'ワタナベ'],
        ['中村', 'ナカムラ'],
        ['小林', 'コバヤシ'],
        ['加藤', 'カトウ'],
    ];

    /** [名, 名のふりがな] の候補一覧。 */
    private const array FIRST_NAMES = [
        ['太郎', 'タロウ'],
        ['花子', 'ハナコ'],
        ['一郎', 'イチロウ'],
        ['花', 'ハナ'],
        ['次郎', 'ジロウ'],
        ['恵子', 'ケイコ'],
        ['直樹', 'ナオキ'],
        ['美咲', 'ミサキ'],
        ['健太', 'ケンタ'],
        ['さくら', 'サクラ'],
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$lastName, $lastNameKana] = fake()->randomElement(self::LAST_NAMES);
        [$firstName, $firstNameKana] = fake()->randomElement(self::FIRST_NAMES);

        return [
            'last_name' => $lastName,
            'first_name' => $firstName,
            'last_name_kana' => $lastNameKana,
            'first_name_kana' => $firstNameKana,
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
