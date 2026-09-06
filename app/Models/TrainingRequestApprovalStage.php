<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TrainingRequestApprovalStageStatus;
use Database\Factories\TrainingRequestApprovalStageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/*
 * 多段階承認における、1段階分の決裁記録のモデルクラス。
 */
#[Fillable(['training_request_id', 'stage_number', 'decided_by_employee_id', 'status', 'decided_at', 'comment'])]
class TrainingRequestApprovalStage extends Model
{
    /** @use HasFactory<TrainingRequestApprovalStageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'stage_number' => 'integer',
            'status' => TrainingRequestApprovalStageStatus::class,
            'decided_at' => 'datetime',
        ];
    }

    public function trainingRequest(): BelongsTo
    {
        return $this->belongsTo(TrainingRequest::class);
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'decided_by_employee_id');
    }
}
