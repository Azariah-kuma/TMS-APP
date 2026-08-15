<?php

declare(strict_types=1);

use App\Actions\Employees\RevokeDelegationAction;
use App\Models\Delegation;

it('immediately excludes the delegation from being active by ending it yesterday', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => now()->subWeek(),
        'ended_at' => null,
    ]);

    $revoked = (new RevokeDelegationAction)->execute($delegation);

    expect($revoked->ended_at->isYesterday())->toBeTrue()
        ->and($revoked->isActive())->toBeFalse();
});

it('cannot end before it started, so a same-day delegation ends on its start date', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $revoked = (new RevokeDelegationAction)->execute($delegation);

    expect($revoked->ended_at->isToday())->toBeTrue();
});

it('does not extend an already-earlier end date', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => now()->subMonth(),
        'ended_at' => now()->subWeek(),
    ]);

    $revoked = (new RevokeDelegationAction)->execute($delegation);

    expect($revoked->ended_at->isSameDay(now()->subWeek()))->toBeTrue();
});
