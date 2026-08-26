import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Position } from '../../../core/models/position';
import { toId } from '../../../core/utils/forms';

/** 挿入位置セレクトの先頭（最上位）を表す特別な値。実際のPositionのidと衝突しない0を使う。 */
const TOP_OPTION_VALUE = 0;

@Component({
  selector: 'app-position-list',
  imports: [ReactiveFormsModule],
  templateUrl: './position-list.html',
})
export class PositionList implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly masterData = inject(MasterDataService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly topOptionValue = TOP_OPTION_VALUE;
  readonly positions = signal<Position[]>([]);
  readonly loading = signal(true);
  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    code: ['', Validators.required],
    after_position_id: [TOP_OPTION_VALUE],
  });

  /** 「操作」を開いている行（誤操作で削除しないよう、通常は畳んでおく）。 */
  readonly openActionsId = signal<number | null>(null);
  /** 編集フォームを表示中の行。 */
  readonly editingId = signal<number | null>(null);
  readonly editSubmitting = signal(false);
  readonly editError = signal<string | null>(null);
  readonly editForm = this.fb.nonNullable.group({
    name: ['', Validators.required],
    code: ['', Validators.required],
  });

  readonly deletingId = signal<number | null>(null);
  readonly deleteError = signal<string | null>(null);

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.masterData.positions().subscribe((positions) => {
      this.positions.set(positions);
      this.loading.set(false);
    });
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.error.set(null);

    const raw = this.form.getRawValue();
    const afterPositionId = toId(raw.after_position_id);

    this.masterData
      .createPosition({
        name: raw.name,
        code: raw.code,
        after_position_id: afterPositionId === TOP_OPTION_VALUE ? null : afterPositionId,
      })
      .subscribe({
        next: () => {
          this.submitting.set(false);
          this.form.reset({ name: '', code: '', after_position_id: TOP_OPTION_VALUE });
          // 挿入によって既存の役職の序列も変わるため、一覧をサーバーから読み直す。
          this.load();
        },
        error: (err) => {
          this.submitting.set(false);
          this.error.set(err.error?.message ?? '役職の追加に失敗しました。入力内容を確認してください。');
        },
      });
  }

  toggleActions(id: number): void {
    this.editingId.set(null);
    this.deleteError.set(null);
    this.openActionsId.set(this.openActionsId() === id ? null : id);
  }

  startEdit(position: Position): void {
    this.editError.set(null);
    this.editForm.reset({ name: position.name, code: position.code });
    this.editingId.set(position.id);
  }

  cancelEdit(): void {
    this.editingId.set(null);
  }

  submitEdit(id: number): void {
    if (this.editForm.invalid) {
      return;
    }

    this.editSubmitting.set(true);
    this.editError.set(null);

    this.masterData.updatePosition(id, this.editForm.getRawValue()).subscribe({
      next: () => {
        this.editSubmitting.set(false);
        this.editingId.set(null);
        this.openActionsId.set(null);
        this.load();
      },
      error: (err) => {
        this.editSubmitting.set(false);
        this.editError.set(err.error?.message ?? '役職の更新に失敗しました。入力内容を確認してください。');
      },
    });
  }

  deletePosition(position: Position): void {
    if (!confirm(`「${position.name}」を削除しますか？この操作は取り消せません。`)) {
      return;
    }

    this.deletingId.set(position.id);
    this.deleteError.set(null);

    this.masterData.deletePosition(position.id).subscribe({
      next: () => {
        this.deletingId.set(null);
        this.openActionsId.set(null);
        this.load();
      },
      error: (err) => {
        this.deletingId.set(null);
        this.deleteError.set(err.error?.message ?? '役職の削除に失敗しました。');
      },
    });
  }
}
