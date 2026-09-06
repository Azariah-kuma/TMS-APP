import { requestStatusLabel, statusLabel } from './status-label';

describe('statusLabel', () => {
  it('not_startedは「未着手」になる', () => {
    expect(statusLabel('not_started')).toBe('未着手');
  });

  it('in_progressは「受講中」になる', () => {
    expect(statusLabel('in_progress')).toBe('受講中');
  });

  it('completedは「完了」になる', () => {
    expect(statusLabel('completed')).toBe('完了');
  });
});

describe('requestStatusLabel', () => {
  it('pendingは「承認待ち」になる', () => {
    expect(requestStatusLabel('pending')).toBe('承認待ち');
  });

  it('approvedは「承認済み」になる', () => {
    expect(requestStatusLabel('approved')).toBe('承認済み');
  });

  it('rejectedは「却下」になる', () => {
    expect(requestStatusLabel('rejected')).toBe('却下');
  });

  it('cancelledは「取消」になる', () => {
    expect(requestStatusLabel('cancelled')).toBe('取消');
  });
});
