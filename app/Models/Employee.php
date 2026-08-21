<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EmployeeRole;
use App\Policies\EmployeePolicy;
use Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

/*
 * 従業員のモデルクラス。
 */
#[Fillable(['user_id', 'employee_code', 'role', 'hired_at', 'retired_at'])]
#[UsePolicy(EmployeePolicy::class)]
class Employee extends Model
{
    /** @use HasFactory<EmployeeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'role' => EmployeeRole::class,
            'hired_at' => 'date',
            'retired_at' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 部署・役職・上司の割り当て履歴（新しい順）。 */
    public function assignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class)->latest('started_at');
    }

    /** 現在有効な（ended_at が NULL の）割り当て。 */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(EmployeeAssignment::class)->whereNull('ended_at');
    }

    /**
     * 自分が上司（manager_id）として指定されている、現在有効な割り当て一覧（＝直属の部下の割り当て）。
     * 一覧画面で「実際に部下を持っているか」を判定する際に使う（withExists等と組み合わせる）。
     */
    public function currentDirectReportAssignments(): HasMany
    {
        return $this->hasMany(EmployeeAssignment::class, 'manager_id')->whereNull('ended_at');
    }

    public function trainingEnrollments(): HasMany
    {
        return $this->hasMany(TrainingEnrollment::class);
    }

    /** 自分が委任元（delegator）となっている委任の一覧。 */
    public function delegationsGiven(): HasMany
    {
        return $this->hasMany(Delegation::class, 'delegator_id');
    }

    /** 自分が代理者（delegate）となっている委任の一覧。 */
    public function delegationsReceived(): HasMany
    {
        return $this->hasMany(Delegation::class, 'delegate_id');
    }

    public function isHr(): bool
    {
        return $this->role === EmployeeRole::Hr;
    }

    /**
     * 自分を頂点とする組織階層下にいる、全ての部下（間接的な部下も含む）のID一覧。
     *
     * 「上司」は固定ロールではなく、有効な EmployeeAssignment.manager_id の連鎖で
     * 決まる相対的な関係のため、都度この階層をたどって判定する。これに加えて、
     * 自分が代理者（delegate）として有効な委任（Delegation）を受けている場合は、
     * その委任元（delegator）の部下も一時的に自分の部下として合流させる
     * （課長代理・兼務カバーなどで、代理期間中だけ相手のチームを参照できるようにするため）。
     *
     * 委任は「委任の委任」を連鎖させない（委任元の直接の組織階層のみを合流させる）。
     * これにより、委任同士が循環しても無限ループにはならない。
     *
     * @return Collection<int, int>
     */
    public function subordinateIds(): Collection
    {
        $ids = $this->hierarchySubordinateIds();

        $activeDelegationsReceived = $this->delegationsReceived()->active()->with('delegator')->get();

        foreach ($activeDelegationsReceived as $delegation) {
            $ids = $ids->merge($delegation->delegator->hierarchySubordinateIds());
        }

        return $ids->unique()->values();
    }

    /**
     * 組織階層（EmployeeAssignment.manager_id の連鎖）のみによる部下のID一覧。
     * 委任（Delegation）による一時的な部下は含まない。
     *
     * 恒久的な組織階層そのものを扱う場面（異動時の循環チェックなど）では、
     * 一時的な委任を含む subordinateIds() ではなく、こちらを使うこと。
     *
     * 階層の深さ分だけクエリが発行される（BFS）。従業員数が数百名規模までは
     * 実用上問題にならないが、大規模組織で1クエリに寄せたい場合は
     * PostgreSQLの再帰CTE（WITH RECURSIVE）への置き換えを検討する。
     *
     * @return Collection<int, int>
     */
    public function hierarchySubordinateIds(): Collection
    {
        $collected = collect();
        $frontier = collect([$this->id]);

        while ($frontier->isNotEmpty()) {
            $children = EmployeeAssignment::query()
                ->whereNull('ended_at')
                ->whereIn('manager_id', $frontier)
                ->pluck('employee_id')
                ->diff($collected)
                ->diff($frontier)
                ->unique()
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $collected = $collected->merge($children);
            $frontier = $children;
        }

        return $collected->values();
    }

    /** $employee が自分の部下（直接・間接、または有効な委任によるものを問わない）であるかどうか。 */
    public function isManagerOf(self $employee): bool
    {
        return $this->subordinateIds()->contains($employee->id);
    }
}
