<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\DepartmentPolicy;
use Database\Factories\DepartmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * 部署のモデルクラス。
 */
#[Fillable(['name', 'code'])]
#[UsePolicy(DepartmentPolicy::class)]
class Department extends Model
{
    /** @use HasFactory<DepartmentFactory> */
    use HasFactory;

    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class);
    }

    /** この部署を閲覧対象者（audience_department_id）に指定している研修一覧。 */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class, 'audience_department_id');
    }
}
