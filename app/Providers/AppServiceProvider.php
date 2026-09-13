<?php

namespace App\Providers;

use App\Models\Department;
use App\Models\DepartmentBudget;
use App\Models\Employee;
use App\Models\Position;
use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;
use App\Models\TrainingRequest;
use App\Models\User;
use App\Observers\AuditLogObserver;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/*
 * アプリケーションサービスプロバイダ。
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // このアプリでは人事（HR）が例外なく全ての操作を行えるため、
        // 各Policyに `$user->employee?->isHr() ?? false` を重複して書く代わりに、
        // ここで一元的に許可する。非HRの場合はnullを返し、各Policyの通常の判定に委ねる。
        Gate::before(fn (User $user, string $ability) => $user->employee?->isHr() ? true : null);

        // レポート閲覧は人事のみ。
        Gate::define('viewReports', fn (User $user): bool => false);

        // 人事・研修管理の主要モデルへの変更操作を汎用的に監査ログへ記録する
        // （異動・委任は専用の履歴テーブルを既に持つため対象外）。
        foreach ([
            Department::class,
            Position::class,
            Employee::class,
            Training::class,
            TrainingLesson::class,
            TrainingRequest::class,
            TrainingEnrollment::class,
            DepartmentBudget::class,
        ] as $auditedModel) {
            $auditedModel::observe(AuditLogObserver::class);
        }
    }
}
