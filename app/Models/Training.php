<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\TrainingPolicy;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * 研修のモデルクラス。
 */
#[Fillable(['title', 'description', 'category', 'is_active'])]
#[UsePolicy(TrainingPolicy::class)]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /** この研修を構成するLesson一覧（表示順）。 */
    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class)->orderBy('position');
    }
}
