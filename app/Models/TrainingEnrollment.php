<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrainingEnrollmentStatus;
use App\Policies\TrainingEnrollmentPolicy;
use Database\Factories\TrainingEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 従業員1名・研修1件に対する受講記録のモデルクラス。
 *
 * progress は全体の進捗率（0〜100）を表し、Lesson完了の集計から自動算出する。
 * Lessonが定義されていない研修のみ、手動更新を許可する。
 */
#[Fillable(['employee_id', 'training_id', 'status', 'progress', 'due_at', 'started_at', 'completed_at'])]
#[UsePolicy(TrainingEnrollmentPolicy::class)]
class TrainingEnrollment extends Model
{
    /** @use HasFactory<TrainingEnrollmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TrainingEnrollmentStatus::class,
            'progress' => 'integer',
            'due_at' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    public function lessonCompletions(): HasMany
    {
        return $this->hasMany(TrainingLessonCompletion::class);
    }

    /**
     * $actor のロールに応じて閲覧可能な受講記録に絞り込む。
     *
     * 人事: 全件／上司: 自分自身と部下（間接的な部下も含む）の受講記録／
     * 一般社員: 自分自身の受講記録のみ。
     */
    #[Scope]
    protected function visibleTo(Builder $query, Employee $actor): void
    {
        if ($actor->isHr()) {
            return;
        }

        $query->whereIn('employee_id', [$actor->id, ...$actor->subordinateIds()]);
    }
}
