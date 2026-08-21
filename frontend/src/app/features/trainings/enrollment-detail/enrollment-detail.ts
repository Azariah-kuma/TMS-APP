import { Component, computed, inject, OnInit, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingEnrollmentService } from '../../../core/services/training-enrollment.service';
import { TrainingEnrollment } from '../../../core/models/training-enrollment';
import { ProgressBar } from '../../../shared/progress-bar/progress-bar';
import { statusLabel } from '../../../shared/status-label';

@Component({
  selector: 'app-enrollment-detail',
  imports: [FormsModule, ProgressBar],
  templateUrl: './enrollment-detail.html',
})
export class EnrollmentDetail implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly service = inject(TrainingEnrollmentService);
  private readonly auth = inject(AuthService);

  readonly statusLabel = statusLabel;
  readonly enrollment = signal<TrainingEnrollment | null>(null);
  readonly loading = signal(true);
  readonly manualProgress = signal(0);
  readonly savingLessonId = signal<number | null>(null);
  readonly savingProgress = signal(false);

  /** 進捗を編集できるのは本人か人事のみ（上司は参照専用）。 */
  readonly canEdit = computed(() => {
    const enrollment = this.enrollment();
    if (!enrollment) {
      return false;
    }

    return this.auth.isHr() || this.auth.currentEmployee()?.id === enrollment.employee_id;
  });

  readonly hasLessons = computed(() => (this.enrollment()?.training?.lessons?.length ?? 0) > 0);

  ngOnInit(): void {
    const id = Number(this.route.snapshot.paramMap.get('id'));

    this.service.get(id).subscribe((enrollment) => {
      this.enrollment.set(enrollment);
      this.manualProgress.set(enrollment.progress);
      this.loading.set(false);
    });
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
}
