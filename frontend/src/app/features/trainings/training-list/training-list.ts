import { Component, inject, OnInit, signal } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { TrainingService } from '../../../core/services/training.service';
import { Department } from '../../../core/models/department';
import { Training } from '../../../core/models/training';
import { toId } from '../../../core/utils/forms';

/** 対象部署セレクトの「指定なし（全員）」を表す特別な値。実際のDepartmentのidと衝突しない0を使う。 */
const NO_DEPARTMENT_VALUE = 0;

@Component({
  selector: 'app-training-list',
  imports: [RouterLink, ReactiveFormsModule],
  templateUrl: './training-list.html',
})
export class TrainingList implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(TrainingService);
  private readonly masterData = inject(MasterDataService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly trainings = signal<Training[]>([]);
  readonly departments = signal<Department[]>([]);
  readonly loading = signal(true);
  readonly showForm = signal(false);
  readonly submitting = signal(false);

  readonly form = this.fb.nonNullable.group({
    title: ['', Validators.required],
    category: [''],
    description: [''],
    audience_department_id: [NO_DEPARTMENT_VALUE],
    audience_managers_only: [false],
    audience_new_hires_only: [false],
    requires_multistage_approval: [false],
    approval_stage_count: [2],
  });

  ngOnInit(): void {
    this.load();

    if (this.isHr()) {
      this.masterData.departments().subscribe((departments) => this.departments.set(departments));
    }
  }

  private load(): void {
    this.service.list().subscribe((trainings) => {
      this.trainings.set(trainings);
      this.loading.set(false);
    });
  }

  /** 対象者の制限を、一覧表示用の短いラベルにまとめる。制限がなければ「全員」。 */
  audienceLabel(training: Training): string {
    const labels: string[] = [];

    if (training.audience_department_id) {
      labels.push(training.audience_department_name ?? '特定部署');
    }
    if (training.audience_managers_only || training.requires_multistage_approval) {
      labels.push('管理職');
    }
    if (training.audience_new_hires_only) {
      labels.push('新入社員');
    }
    if (training.requires_multistage_approval) {
      labels.push(`多段階承認必須(${training.approval_stage_count}段階)`);
    }

    return labels.length > 0 ? labels.join('・') : '全員';
  }

  submit(): void {
    if (this.form.invalid) {
      return;
    }

    this.submitting.set(true);

    const raw = this.form.getRawValue();
    const audienceDepartmentId = toId(raw.audience_department_id);

    this.service
      .create({
        ...raw,
        audience_department_id: audienceDepartmentId === NO_DEPARTMENT_VALUE ? null : audienceDepartmentId,
        approval_stage_count: raw.requires_multistage_approval ? raw.approval_stage_count : null,
      })
      .subscribe({
        next: () => {
          this.form.reset({
            title: '',
            category: '',
            description: '',
            audience_department_id: NO_DEPARTMENT_VALUE,
            audience_managers_only: false,
            audience_new_hires_only: false,
            requires_multistage_approval: false,
            approval_stage_count: 2,
          });
          this.showForm.set(false);
          this.submitting.set(false);
          this.load();
        },
        error: () => this.submitting.set(false),
      });
  }
}
