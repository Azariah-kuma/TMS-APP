<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Support\FiscalYear;
use Illuminate\Database\Eloquent\Factories\Factory;

/*
 * 部署の年度研修予算のファクトリクラス。
 */

/** @extends Factory<DepartmentBudget> */
class DepartmentBudgetFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'fiscal_year' => FiscalYear::current(),
            'budget_amount' => fake()->numberBetween(100, 1000) * 1000,
        ];
    }
}
