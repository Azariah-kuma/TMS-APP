import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { TrainingService } from '../../../core/services/training.service';
import { Training } from '../../../core/models/training';

@Component({
  selector: 'app-training-list',
  imports: [RouterLink, ReactiveFormsModule],
  templateUrl: './training-list.html',
})
export class TrainingList implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(TrainingService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly trainings = signal<Training[]>([]);
  readonly loading = signal(true);
  readonly showForm = signal(false);
  readonly submitting = signal(false);

  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    category: [''],
    description: [''],
  });

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.service.list().subscribe((trainings) => {
      this.trainings.set(trainings);
      this.loading.set(false);
    });
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);

    this.service.create(this.form.getRawValue()).subscribe({
      next: () => {
        this.form.reset({ title: '', category: '', description: '' });
        this.showForm.set(false);
        this.submitting.set(false);
        this.load();
      },
      error: () => this.submitting.set(false),
    });
  }
}
