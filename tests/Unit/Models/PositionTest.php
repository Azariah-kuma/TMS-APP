<?php

declare(strict_types=1);

use App\Models\EmployeeAssignment;
use App\Models\Position;

it('役職に属する配属一覧を取得できる', function () {
    $position = Position::factory()->create();
    $assignment = EmployeeAssignment::factory()->create(['position_id' => $position->id]);

    expect($position->assignments()->pluck('id'))->toEqual(collect([$assignment->id]));
});

it('新規作成した役職には配属が存在しない', function () {
    $position = Position::factory()->create();

    expect($position->assignments()->count())->toBe(0);
});
