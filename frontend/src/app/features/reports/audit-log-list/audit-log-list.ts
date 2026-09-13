import { DatePipe } from '@angular/common';
import { Component, OnInit, inject, signal } from '@angular/core';
import { AuditLogService } from '../../../core/services/audit-log.service';
import { AuditLog, AuditLogAction, AuditLogPage } from '../../../core/models/audit-log';

/** 監査ログ画面で絞り込み対象とするモデルの選択肢（Observerを登録している主要モデルのみ）。 */
export const AUDITABLE_TYPES = [
  'Department',
  'Position',
  'Employee',
  'Training',
  'TrainingLesson',
  'TrainingRequest',
  'TrainingEnrollment',
  'DepartmentBudget',
] as const;

const ACTION_LABELS: Record<AuditLogAction, string> = {
  created: '作成',
  updated: '更新',
  deleted: '削除',
};

/** auditable_type（モデルの短縮クラス名）の日本語表示名。 */
const AUDITABLE_TYPE_LABELS: Record<(typeof AUDITABLE_TYPES)[number], string> = {
  Department: '部署',
  Position: '役職',
  Employee: '従業員',
  Training: '研修',
  TrainingLesson: '研修レッスン',
  TrainingRequest: '研修申請',
  TrainingEnrollment: '受講記録',
  DepartmentBudget: '部署予算',
};

@Component({
  selector: 'app-audit-log-list',
  imports: [DatePipe],
  templateUrl: './audit-log-list.html',
})
export class AuditLogList implements OnInit {
  private readonly service = inject(AuditLogService);

  readonly auditableTypes = AUDITABLE_TYPES;
  readonly filterType = signal('');
  readonly page = signal<AuditLogPage | null>(null);
  readonly loading = signal(true);

  ngOnInit(): void {
    this.load();
  }

  private load(): void {
    this.loading.set(true);

    this.service.list({ auditableType: this.filterType() || undefined }).subscribe((page) => {
      this.page.set(page);
      this.loading.set(false);
    });
  }

  applyFilter(type: string): void {
    this.filterType.set(type);
    this.load();
  }

  actionLabel(log: AuditLog): string {
    return ACTION_LABELS[log.action];
  }

  /** auditable_type（英語のクラス名）を日本語表示名に変換する。未知の型はそのまま表示する。 */
  auditableTypeLabel(type: string): string {
    return (AUDITABLE_TYPE_LABELS as Record<string, string>)[type] ?? type;
  }
}
