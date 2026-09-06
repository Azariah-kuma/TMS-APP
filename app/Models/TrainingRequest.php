<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrainingRequestStatus;
use App\Policies\TrainingRequestPolicy;
use Database\Factories\TrainingRequestFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 従業員による研修受講申請のモデルクラス。
 */
#[Fillable([
    'employee_id',
    'requested_by_employee_id',
    'training_id',
    'status',
    'reason',
    'due_at',
    'required_approval_stages',
    'current_approval_stage',
    'decided_by_employee_id',
    'decided_at',
    'decision_comment',
    'training_enrollment_id',
])]
#[UsePolicy(TrainingRequestPolicy::class)]
class TrainingRequest extends Model
{
    /** @use HasFactory<TrainingRequestFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => TrainingRequestStatus::class,
            'due_at' => 'date',
            'required_approval_stages' => 'integer',
            'current_approval_stage' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** 申請した従業員（本人申請なら employee と同じ、上司による代理申請ならその上司）。 */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'requested_by_employee_id');
    }

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /** 承認/却下した従業員（上司またはHR）。 */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'decided_by_employee_id');
    }

    /** 承認によって作られた受講記録（承認前はnull）。 */
    public function trainingEnrollment(): BelongsTo
    {
        return $this->belongsTo(TrainingEnrollment::class);
    }

    /** 本人自身が申請したものか（false の場合は上司による代理申請）。 */
    public function isSelfRequested(): bool
    {
        return $this->requested_by_employee_id === $this->employee_id;
    }

    /** 各段階の決裁記録（段階番号順）。多段階承認でない申請（required_approval_stages=1）でも、承認済みなら1件だけ持つ。 */
    public function approvalHistory(): HasMany
    {
        return $this->hasMany(TrainingRequestApprovalStage::class)->orderBy('stage_number');
    }

    /**
     * 現在保留中の段階を承認・却下できる資格があるのは「誰の上司か」を判定するための対象従業員。
     * 1段階目は申請対象の本人。2段階目以降は、直前の段階を決裁した人（その人の上司が次の決裁者になる）。
     */
    public function currentStageApprovalTarget(): Employee
    {
        if ($this->current_approval_stage <= 1) {
            return $this->employee;
        }

        $previousStage = $this->approvalHistory()
            ->where('stage_number', $this->current_approval_stage - 1)
            ->first();

        return $previousStage?->decidedBy ?? $this->employee;
    }

    /**
     * $actor のロールに応じて閲覧可能な申請に絞り込む。
     *
     * 人事: 全件／上司: 自分自身と部下（間接的な部下も含む）の申請／
     * 一般社員: 自分自身の申請のみ。
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
