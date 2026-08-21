import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { isFieldInvalid } from '../../../core/utils/forms';

@Component({
  selector: 'app-set-password',
  imports: [ReactiveFormsModule],
  templateUrl: './set-password.html',
})
export class SetPassword implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly auth = inject(AuthService);

  readonly error = signal<string | null>(null);
  readonly submitting = signal(false);
  readonly linkInvalid = signal(false);

  private token = '';
  readonly email = signal('');

  readonly form = this.fb.nonNullable.group({
    password: ['', [Validators.required, Validators.minLength(8)]],
    password_confirmation: ['', Validators.required],
  });

  ngOnInit(): void {
    const params = this.route.snapshot.queryParamMap;
    this.token = params.get('token') ?? '';
    const email = params.get('email') ?? '';
    this.email.set(email);

    if (!this.token || !email) {
      this.linkInvalid.set(true);
    }
  }

  fieldInvalid(name: keyof typeof this.form.controls): boolean {
    return isFieldInvalid(this.form, name);
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);
    this.error.set(null);

    this.auth
      .setPassword({
        token: this.token,
        email: this.email(),
        ...this.form.getRawValue(),
      })
      .subscribe({
        next: () => this.router.navigateByUrl('/dashboard'),
        error: (err) => {
          this.submitting.set(false);
          this.error.set(
            err.error?.message ?? '設定に失敗しました。招待リンクの有効期限が切れている可能性があります。',
          );
        },
      });
  }
}
