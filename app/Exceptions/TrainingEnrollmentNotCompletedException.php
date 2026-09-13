<?php

declare(strict_types=1);

namespace App\Exceptions;

/*
 * 受講が完了していない受講記録に対して、研修効果測定（アンケート・テスト）を
 * 提出しようとした場合に投げられる例外。
 */
final class TrainingEnrollmentNotCompletedException extends UnprocessableDomainException {}
