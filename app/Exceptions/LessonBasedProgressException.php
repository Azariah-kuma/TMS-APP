<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 研修Lessonに基づく進捗が不正な場合に投げられる例外。
 */
final class LessonBasedProgressException extends UnprocessableDomainException {}
