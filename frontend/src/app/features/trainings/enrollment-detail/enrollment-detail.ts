import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, FormsModule, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingFeedbackService } from '../../../core/services/training-feedback.service';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { TrainingLesson } from '../../../core/models/training-lesson';
import { ProgressBar } from '../../../shared/progress-bar/progress-bar';
import { statusLabel } from '../../../shared/status-label';

@Component({
  selector: 'app-enrollment-detail',
  imports: [FormsModule, ReactiveFormsModule, ProgressBar],
  templateUrl: './enrollment-detail.html',
})
export class EnrollmentDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly service = inject(TrainingEnrollmentService);
  private readonly feedbackService = inject(TrainingFeedbackService);
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);

  readonly statusLabel = statusLabel;
  readonly enrollment = signal<TrainingEnrollment | null>(null);
  readonly loading = signal(true);
  readonly manualProgress = signal(0);
  readonly savingLessonId = signal<number | null>(null);
  readonly savingProgress = signal(false);

  readonly feedbackForm = this.fb.nonNullable.group({
    satisfaction_score: [5, [Validators.required, Validators.min(1), Validators.max(5)]],
    understanding_score: [5, [Validators.required, Validators.min(1), Validators.max(5)]],
    quiz_score: [null as number | null],
    comment: [''],
  });
  readonly feedbackSubmitting = signal(false);
  readonly feedbackError = signal<string | null>(null);

  /** 進捗を編集できるのは本人か人事のみ（上司は参照専用）。 */
  readonly canEdit = computed(() => {
    const enrollment = this.enrollment();
    if (!enrollment) {
      return false;
    }

    return this.auth.isHr() || this.auth.currentEmployee()?.id === enrollment.employee_id;
  });

  readonly hasLessons = computed(() => (this.enrollment()?.training?.lessons?.length ?? 0) > 0);

  /** 研修効果測定（アンケート・テスト）を提出できるのは、受講完了済みの本人のみ、かつ未提出の場合。 */
  readonly canSubmitFeedback = computed(() => {
    const enrollment = this.enrollment();
    if (!enrollment) {
      return false;
    }

    return (
      enrollment.status === 'completed' &&
      this.auth.currentEmployee()?.id === enrollment.employee_id &&
      !enrollment.training_feedback
    );
  });

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.service.get(id).subscribe((enrollment) => {
      this.enrollment.set(enrollment);
      this.manualProgress.set(enrollment.progress);
      this.loading.set(false);
    });
  }

  /** 添付教材のいずれかが動画であれば、そのLessonは動画視聴完了で自動的に完了扱いにする表示にする。 */
  hasVideoAttachment(lesson: TrainingLesson): boolean {
    return lesson.attachments.some((attachment) => attachment.mime_type.startsWith('video/'));
  }

  isLessonCompleted(lessonId: number): boolean {
    return this.enrollment()?.completed_lesson_ids?.includes(lessonId) ?? false;
  }

  toggleLesson(lessonId: number): void {
    const enrollment = this.enrollment();
    if (!enrollment) {
      return;
    }

    this.savingLessonId.set(lessonId);

    const request = this.isLessonCompleted(lessonId)
      ? this.service.uncompleteLesson(enrollment.id, lessonId)
      : this.service.completeLesson(enrollment.id, lessonId);

    request.subscribe({
      next: (updated) => {
        this.enrollment.set(updated);
        this.savingLessonId.set(null);
      },
      error: () => this.savingLessonId.set(null),
    });
  }

  /** 動画を最後まで再生したら、（未完了であれば）自動的に完了にする。動画は手動でチェックを入れさせない。 */
  onVideoEnded(lessonId: number): void {
    if (this.canEdit() && !this.isLessonCompleted(lessonId)) {
      this.toggleLesson(lessonId);
    }
  }

  submitManualProgress(): void {
    const enrollment = this.enrollment();
    if (!enrollment) {
      return;
    }

    this.savingProgress.set(true);

    this.service.updateProgress(enrollment.id, this.manualProgress()).subscribe({
      next: (updated) => {
        this.enrollment.set(updated);
        this.savingProgress.set(false);
      },
      error: () => this.savingProgress.set(false),
    });
  }

  submitFeedback(): void {
    const enrollment = this.enrollment();
    if (!enrollment || this.feedbackForm.invalid) {
      return;
    }

    this.feedbackSubmitting.set(true);
    this.feedbackError.set(null);

    this.feedbackService.submit(enrollment.id, this.feedbackForm.getRawValue()).subscribe({
      next: (feedback) => {
        this.feedbackSubmitting.set(false);
        this.enrollment.set({ ...enrollment, training_feedback: feedback });
      },
      error: (err) => {
        this.feedbackSubmitting.set(false);
        this.feedbackError.set(err.error?.message ?? '研修効果測定の提出に失敗しました。');
      },
    });
  }
}
