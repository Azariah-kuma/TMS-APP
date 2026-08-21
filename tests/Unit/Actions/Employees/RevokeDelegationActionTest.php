<?php

declare(strict_types=1);

use App\Actions\Employees\RevokeDelegationAction;
use App\Models\Delegation;

it('終了日を昨日にすることで委任を即座に無効化する', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => now()->subWeek(),
        'ended_at' => null,
    ]);

    $revoked = (new RevokeDelegationAction)->execute($delegation);

    expect($revoked->ended_at->isYesterday())->toBeTrue()
        ->and($revoked->isActive())->toBeFalse();
});

it('開始日より前には終了できないため、当日開始の委任は開始日と同日に終了する', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => now(),
        'ended_at' => null,
    ]);

    $revoked = (new RevokeDelegationAction)->execute($delegation);

    expect($revoked->ended_at->isToday())->toBeTrue();
});

it('既に設定済みの、より早い終了日を延長しない', function () {
    $delegation = Delegation::factory()->create([
        'started_at' => now()->subMonth(),
        'ended_at' => now()->subWeek(),
    ]);

    $revoked = (new RevokeDelegationAction)->execute($delegation);

    expect($revoked->ended_at->isSameDay(now()->subWeek()))->toBeTrue();
});
