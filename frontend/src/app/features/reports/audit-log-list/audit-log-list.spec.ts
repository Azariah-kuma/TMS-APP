import { TestBed } from '@angular/core/testing';
import { of } from 'rxjs';
import { vi } from 'vitest';
import { AuditLogService } from '../../../core/services/audit-log.service';
import { AuditLog, AuditLogPage } from '../../../core/models/audit-log';
import { AuditLogList } from './audit-log-list';

function makePage(overrides: Partial<AuditLogPage> = {}): AuditLogPage {
  return { data: [], current_page: 1, last_page: 1, total: 0, ...overrides };
}

function makeLog(overrides: Partial<AuditLog> = {}): AuditLog {
  return {
    id: 1,
    auditable_type: 'Department',
    auditable_id: 1,
    action: 'created',
    actor_employee_id: 1,
    actor_name: '人事 太郎',
    changes: { name: '営業部' },
    created_at: '2026-09-06T00:00:00Z',
    ...overrides,
  };
}

describe('AuditLogList', () => {
  function createComponent(serviceMock: Partial<AuditLogService>) {
    TestBed.configureTestingModule({
      imports: [AuditLogList],
      providers: [{ provide: AuditLogService, useValue: serviceMock }],
    });

    const fixture = TestBed.createComponent(AuditLogList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時にフィルタ無しで一覧を読み込む', () => {
    const list = vi.fn().mockReturnValue(of(makePage({ data: [makeLog()] })));
    const fixture = createComponent({ list });

    expect(list).toHaveBeenCalledWith({ auditableType: undefined });
    expect(fixture.componentInstance.page()?.data.length).toBe(1);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('applyFilterは指定した対象で絞り込み直す', () => {
    const list = vi.fn().mockReturnValue(of(makePage()));
    const fixture = createComponent({ list });

    fixture.componentInstance.applyFilter('Training');

    expect(fixture.componentInstance.filterType()).toBe('Training');
    expect(list).toHaveBeenLastCalledWith({ auditableType: 'Training' });
  });

  it('actionLabelは操作種別を日本語に変換する', () => {
    const fixture = createComponent({ list: () => of(makePage()) });

    expect(fixture.componentInstance.actionLabel(makeLog({ action: 'created' }))).toBe('作成');
    expect(fixture.componentInstance.actionLabel(makeLog({ action: 'updated' }))).toBe('更新');
    expect(fixture.componentInstance.actionLabel(makeLog({ action: 'deleted' }))).toBe('削除');
  });

  it('auditableTypeLabelは対象モデルの英語クラス名を日本語に変換する', () => {
    const fixture = createComponent({ list: () => of(makePage()) });

    expect(fixture.componentInstance.auditableTypeLabel('Department')).toBe('部署');
    expect(fixture.componentInstance.auditableTypeLabel('TrainingEnrollment')).toBe('受講記録');
  });

  it('auditableTypeLabelは未知の型はそのまま返す', () => {
    const fixture = createComponent({ list: () => of(makePage()) });

    expect(fixture.componentInstance.auditableTypeLabel('Unknown')).toBe('Unknown');
  });
});
