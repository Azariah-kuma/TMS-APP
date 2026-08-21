import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { Position } from '../../../core/models/position';

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
  readonly positions = signal<Position[]>([]);
  readonly loading = signal(true);
  readonly submitting = signal(false);
  readonly error = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    name: ['', Validators.required],
    code: ['', Validators.required],
    rank: [1, [Validators.required, Validators.min(0)]],
  });

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

    this.masterData.createPosition({ ...raw, rank: Number(raw.rank) }).subscribe({
      next: (position) => {
        this.submitting.set(false);
        this.positions.update((list) => [...list, position].sort((a, b) => a.rank - b.rank));
        this.form.reset({ name: '', code: '', rank: 1 });
      },
      error: (err) => {
        this.submitting.set(false);
        this.error.set(err.error?.message ?? '役職の追加に失敗しました。入力内容を確認してください。');
      },
    });
  }
}
