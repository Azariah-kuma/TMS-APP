<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 既に研修効果測定（アンケート・テスト）を提出済みの受講記録に対して、
 * 再度提出しようとした場合に投げられる例外。
 */
final class TrainingFeedbackAlreadySubmittedException extends UnprocessableDomainException {}
