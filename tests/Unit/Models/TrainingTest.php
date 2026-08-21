<?php

declare(strict_types=1);

use App\Models\Training;
use App\Models\TrainingEnrollment;
use App\Models\TrainingLesson;

it('研修に属する受講記録一覧を取得できる', function () {
    $training = Training::factory()->create();
    $enrollment = TrainingEnrollment::factory()->create(['training_id' => $training->id]);

    expect($training->enrollments()->pluck('id'))->toEqual(collect([$enrollment->id]));
});

it('position順にLesson一覧を取得できる', function () {
    $training = Training::factory()->create();
    $second = TrainingLesson::factory()->for($training)->create(['position' => 2]);
    $first = TrainingLesson::factory()->for($training)->create(['position' => 1]);

    expect($training->lessons()->pluck('id')->all())->toBe([$first->id, $second->id]);
});
