<?php

declare(strict_types=1);

use App\Actions\Employees\DeletePositionAction;
use App\Exceptions\PositionInUseException;
use App\Models\EmployeeAssignment;
use App\Models\Position;

it('使われていない役職を削除する', function () {
    $position = Position::factory()->create();

    (new DeletePositionAction)->execute($position);

    expect(Position::find($position->id))->toBeNull();
});

it('配属履歴で使用されている役職は削除できない', function () {
    $position = Position::factory()->create();
    EmployeeAssignment::factory()->create(['position_id' => $position->id]);

    expect(fn () => (new DeletePositionAction)->execute($position))
        ->toThrow(PositionInUseException::class);

    expect(Position::find($position->id))->not->toBeNull();
});
