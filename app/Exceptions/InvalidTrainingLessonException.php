<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 研修Lessonが不正な場合に投げられる例外。
 */
final class InvalidTrainingLessonException extends UnprocessableDomainException {}
