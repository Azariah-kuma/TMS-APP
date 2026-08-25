<?php

namespace App\Providers;

use App\Models\User;
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
    }
}
