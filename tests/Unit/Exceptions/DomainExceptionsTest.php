<?php

declare(strict_types=1);

use App\Exceptions\AlreadyEnrolledException;
use App\Exceptions\EmployeeRetiredException;
use App\Exceptions\InvalidAssignmentPeriodException;
use App\Exceptions\InvalidDelegationException;
use App\Exceptions\InvalidPasswordResetTokenException;
use App\Exceptions\InvalidTrainingLessonException;
use App\Exceptions\InviteEmailFailedException;
use Illuminate\Http\Request;

it('例外メッセージを含む422のJSONレスポンスとしてrenderされる', function (string $exceptionClass) {
    $exception = new $exceptionClass('テストメッセージ');

    $response = $exception->render(Request::create('/'));

    expect($response->getStatusCode())->toBe(422)
        ->and($response->getData(true))->toBe(['message' => 'テストメッセージ']);
})->with([
    AlreadyEnrolledException::class,
    EmployeeRetiredException::class,
    InvalidAssignmentPeriodException::class,
    InvalidDelegationException::class,
    InvalidPasswordResetTokenException::class,
    InvalidTrainingLessonException::class,
    InviteEmailFailedException::class,
]);
