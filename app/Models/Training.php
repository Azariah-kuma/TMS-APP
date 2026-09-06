<?php

declare(strict_types=1);

namespace App\Models;

use App\Policies\TrainingPolicy;
use Database\Factories\TrainingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/*
 * 研修のモデルクラス。
 */
#[Fillable([
    'title',
    'description',
    'category',
    'is_active',
    'audience_department_id',
    'audience_managers_only',
    'audience_new_hires_only',
    'requires_multistage_approval',
    'approval_stage_count',
])]
#[UsePolicy(TrainingPolicy::class)]
class Training extends Model
{
    /** @use HasFactory<TrainingFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'audience_managers_only' => 'boolean',
            'audience_new_hires_only' => 'boolean',
            'requires_multistage_approval' => 'boolean',
            'approval_stage_count' => 'integer',
        ];
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    public function trainingRequests(): HasMany
    {
        return $this->hasMany(TrainingRequest::class);
    }

    /** この研修を構成するLesson一覧（表示順）。 */
    public function lessons(): HasMany
    {
        return $this->hasMany(TrainingLesson::class)->orderBy('position');
    }

    /** 閲覧対象者を部署で絞り込んでいる場合の、その部署。 */
    public function audienceDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'audience_department_id');
    }

    /**
     * $actor がこの研修を閲覧できるかどうか（対象者の制限に基づく判定）。
     *
     * 対象部署／管理職／今年度入社の新入社員のいずれもOR条件で、1つも設定されて
     * いなければ全員に公開される。人事は Gate::before で別途無条件に許可されるため、
     * ここでは判定しない。
     *
     * requires_multistage_approval（多段階承認が必要）な研修は、承認者になり得る
     * 管理職に事前に把握してもらう必要があるため、audience_managers_only の設定に
     * 関わらず常に管理職にも表示される。
     */
    public function isVisibleTo(Employee $actor): bool
    {
        if (! $this->hasAudienceRestriction()) {
            return true;
        }

        if ($this->audience_department_id !== null
            && $this->audience_department_id === $actor->currentAssignment?->department_id) {
            return true;
        }

        if (($this->audience_managers_only || $this->requires_multistage_approval) && $actor->isManager()) {
            return true;
        }

        if ($this->audience_new_hires_only && $actor->hiredInCurrentFiscalYear()) {
            return true;
        }

        return false;
    }

    private function hasAudienceRestriction(): bool
    {
        return $this->audience_department_id !== null
            || $this->audience_managers_only
            || $this->audience_new_hires_only
            || $this->requires_multistage_approval;
    }

    /**
     * $actor が閲覧できる研修に絞り込む。
     *
     * 人事: 全件／それ以外: 対象者の制限が設定されていない研修（全員向け）、
     * または対象者の条件（所属部署・管理職・今年度入社）のいずれかに合致する研修。
     */
    #[Scope]
    protected function visibleTo(Builder $query, Employee $actor): void
    {
        if ($actor->isHr()) {
            return;
        }

        $departmentId = $actor->currentAssignment?->department_id;
        $isManager = $actor->isManager();
        $isNewHire = $actor->hiredInCurrentFiscalYear();

        $query->where(function (Builder $q) use ($departmentId, $isManager, $isNewHire) {
            $q->where(function (Builder $unrestricted) {
                $unrestricted->whereNull('audience_department_id')
                    ->where('audience_managers_only', false)
                    ->where('audience_new_hires_only', false)
                    ->where('requires_multistage_approval', false);
            });

            if ($departmentId !== null) {
                $q->orWhere('audience_department_id', $departmentId);
            }

            if ($isManager) {
                $q->orWhere('audience_managers_only', true);
                $q->orWhere('requires_multistage_approval', true);
            }

            if ($isNewHire) {
                $q->orWhere('audience_new_hires_only', true);
            }
        });
    }
}
