<?php

declare(strict_types=1);

use App\Models\TrainingEnrollment;
use App\Models\TrainingRequest;

it('承認によって作られた受講記録を参照できる', function () {
    $enrollment = TrainingEnrollment::factory()->create();
    $request = TrainingRequest::factory()->approved()->create(['training_enrollment_id' => $enrollment->id]);

    expect($request->trainingEnrollment->is($enrollment))->toBeTrue();
});

it('承認前は受講記録が紐付いていない', function () {
    $request = TrainingRequest::factory()->create();

    expect($request->trainingEnrollment)->toBeNull();
});
