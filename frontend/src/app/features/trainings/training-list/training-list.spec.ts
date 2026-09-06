import { provideRouter } from '@angular/router';
import { TestBed } from '@angular/core/testing';
import { of, throwError } from 'rxjs';
import { vi } from 'vitest';
import { AuthService } from '../../../core/services/auth.service';
import { MasterDataService } from '../../../core/services/master-data.service';
import { TrainingService } from '../../../core/services/training.service';
import { Training } from '../../../core/models/training';
import { TrainingList } from './training-list';

function makeTraining(overrides: Partial<Training> = {}): Training {
  return {
    id: 1,
    title: '情報セキュリティ研修',
    description: null,
    category: null,
    is_active: true,
    audience_department_id: null,
    audience_managers_only: false,
    audience_new_hires_only: false,
    requires_multistage_approval: false,
    approval_stage_count: null,
    ...overrides,
  };
}

describe('TrainingList', () => {
  function createComponent(
    trainingServiceMock: Partial<TrainingService>,
    masterDataMock: Partial<MasterDataService> = {},
    isHr = true,
  ) {
    TestBed.configureTestingModule({
      imports: [TrainingList],
      providers: [
        provideRouter([]),
        { provide: TrainingService, useValue: trainingServiceMock },
        { provide: MasterDataService, useValue: { departments: () => of([]), ...masterDataMock } },
        { provide: AuthService, useValue: { isHr: () => isHr } },
      ],
    });

    const fixture = TestBed.createComponent(TrainingList);
    fixture.detectChanges();
    return fixture;
  }

  it('初期化時に研修一覧を読み込む', () => {
    const trainings = [makeTraining()];
    const fixture = createComponent({ list: () => of(trainings) });

    expect(fixture.componentInstance.trainings()).toEqual(trainings);
    expect(fixture.componentInstance.loading()).toBe(false);
  });

  it('人事の場合のみ部署一覧を読み込む', () => {
    const departmentsSpy = vi.fn().mockReturnValue(of([]));
    createComponent({ list: () => of([]) }, { departments: departmentsSpy }, true);
    expect(departmentsSpy).toHaveBeenCalled();
  });

  it('人事でない場合は部署一覧を読み込まない', () => {
    const departmentsSpy = vi.fn().mockReturnValue(of([]));
    createComponent({ list: () => of([]) }, { departments: departmentsSpy }, false);
    expect(departmentsSpy).not.toHaveBeenCalled();
  });

  describe('audienceLabel', () => {
    it('何も設定されていなければ「全員」を返す', () => {
      const fixture = createComponent({ list: () => of([]) });
      expect(fixture.componentInstance.audienceLabel(makeTraining())).toBe('全員');
    });

    it('対象部署が設定されていれば部署名を含める', () => {
      const fixture = createComponent({ list: () => of([]) });
      const training = makeTraining({ audience_department_id: 1, audience_department_name: '開発部' });
      expect(fixture.componentInstance.audienceLabel(training)).toBe('開発部');
    });

    it('部署名が無ければ「特定部署」を表示する', () => {
      const fixture = createComponent({ list: () => of([]) });
      const training = makeTraining({ audience_department_id: 1 });
      expect(fixture.componentInstance.audienceLabel(training)).toBe('特定部署');
    });

    it('管理職のみ対象なら「管理職」を含める', () => {
      const fixture = createComponent({ list: () => of([]) });
      expect(fixture.componentInstance.audienceLabel(makeTraining({ audience_managers_only: true }))).toBe('管理職');
    });

    it('新入社員のみ対象なら「新入社員」を含める', () => {
      const fixture = createComponent({ list: () => of([]) });
      expect(fixture.componentInstance.audienceLabel(makeTraining({ audience_new_hires_only: true }))).toBe(
        '新入社員',
      );
    });

    it('多段階承認が必要なら、管理職と重複させず段階数付きの表記を追加する', () => {
      const fixture = createComponent({ list: () => of([]) });
      const training = makeTraining({ requires_multistage_approval: true, approval_stage_count: 2 });
      expect(fixture.componentInstance.audienceLabel(training)).toBe('管理職・多段階承認必須(2段階)');
    });

    it('複数条件は「・」で連結する', () => {
      const fixture = createComponent({ list: () => of([]) });
      const training = makeTraining({
        audience_department_id: 1,
        audience_department_name: '開発部',
        audience_new_hires_only: true,
      });
      expect(fixture.componentInstance.audienceLabel(training)).toBe('開発部・新入社員');
    });
  });

  describe('submit', () => {
    it('無効なフォームでは何もしない', () => {
      const create = vi.fn();
      const fixture = createComponent({ list: () => of([]), create });

      fixture.componentInstance.form.patchValue({ title: '' });
      fixture.componentInstance.submit();

      expect(create).not.toHaveBeenCalled();
    });

    it('対象部署が「指定なし」(0)のままなら、audience_department_idはnullで送る', () => {
      const create = vi.fn().mockReturnValue(of(makeTraining()));
      const fixture = createComponent({ list: () => of([]), create });

      fixture.componentInstance.form.patchValue({ title: '研修A' });
      fixture.componentInstance.submit();

      expect(create).toHaveBeenCalledWith(
        expect.objectContaining({ title: '研修A', audience_department_id: null }),
      );
    });

    it('対象部署を指定した場合、そのIDを送る', () => {
      const create = vi.fn().mockReturnValue(of(makeTraining()));
      const fixture = createComponent({ list: () => of([]), create });

      fixture.componentInstance.form.patchValue({ title: '研修A', audience_department_id: 3 });
      fixture.componentInstance.submit();

      expect(create).toHaveBeenCalledWith(expect.objectContaining({ audience_department_id: 3 }));
    });

    it('多段階承認のチェックが入っていなければ、approval_stage_countはnullで送る', () => {
      const create = vi.fn().mockReturnValue(of(makeTraining()));
      const fixture = createComponent({ list: () => of([]), create });

      fixture.componentInstance.form.patchValue({ title: '研修A', requires_multistage_approval: false });
      fixture.componentInstance.submit();

      expect(create).toHaveBeenCalledWith(expect.objectContaining({ approval_stage_count: null }));
    });

    it('多段階承認のチェックが入っていれば、approval_stage_countをそのまま送る', () => {
      const create = vi.fn().mockReturnValue(of(makeTraining()));
      const fixture = createComponent({ list: () => of([]), create });

      fixture.componentInstance.form.patchValue({
        title: '研修A',
        requires_multistage_approval: true,
        approval_stage_count: 3,
      });
      fixture.componentInstance.submit();

      expect(create).toHaveBeenCalledWith(expect.objectContaining({ approval_stage_count: 3 }));
    });

    it('成功するとフォームを閉じ、一覧を再読み込みする', () => {
      const listSpy = vi.fn().mockReturnValue(of([]));
      const create = vi.fn().mockReturnValue(of(makeTraining()));
      const fixture = createComponent({ list: listSpy, create });

      fixture.componentInstance.showForm.set(true);
      fixture.componentInstance.form.patchValue({ title: '研修A' });
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.showForm()).toBe(false);
      expect(fixture.componentInstance.submitting()).toBe(false);
      expect(listSpy).toHaveBeenCalledTimes(2);
    });

    it('失敗するとsubmittingをfalseに戻す', () => {
      const create = vi.fn().mockReturnValue(throwError(() => new Error('failed')));
      const fixture = createComponent({ list: () => of([]), create });

      fixture.componentInstance.form.patchValue({ title: '研修A' });
      fixture.componentInstance.submit();

      expect(fixture.componentInstance.submitting()).toBe(false);
    });
  });
});
