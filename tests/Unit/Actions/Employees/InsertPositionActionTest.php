<?php

declare(strict_types=1);

use App\Actions\Employees\InsertPositionAction;
use App\Models\Position;

it('after_position_idを指定しない場合は最上位（rank=1）に挿入する', function () {
    $existing = Position::factory()->create(['rank' => 1]);

    $position = (new InsertPositionAction)->execute('部長', 'POS-GM', null);

    expect($position->rank)->toBe(1)
        ->and($existing->fresh()->rank)->toBe(2);
});

it('指定した役職の直後に挿入し、以降の役職を1つずつ繰り下げる', function () {
    $sectionChief = Position::factory()->create(['rank' => 1]);
    $seniorStaff = Position::factory()->create(['rank' => 2]);
    $staff = Position::factory()->create(['rank' => 3]);

    $position = (new InsertPositionAction)->execute('班長', 'POS-TL', $sectionChief->id);

    expect($position->rank)->toBe(2)
        ->and($sectionChief->fresh()->rank)->toBe(1)
        ->and($seniorStaff->fresh()->rank)->toBe(3)
        ->and($staff->fresh()->rank)->toBe(4);
});

it('最後尾の役職の直後に挿入する場合、他の役職には影響しない', function () {
    $first = Position::factory()->create(['rank' => 1]);
    $last = Position::factory()->create(['rank' => 2]);

    $position = (new InsertPositionAction)->execute('新設役職', 'POS-NEW', $last->id);

    expect($position->rank)->toBe(3)
        ->and($first->fresh()->rank)->toBe(1)
        ->and($last->fresh()->rank)->toBe(2);
});

it('役職が1件も無い状態で最上位に挿入できる', function () {
    $position = (new InsertPositionAction)->execute('部長', 'POS-GM', null);

    expect($position->rank)->toBe(1);
});
