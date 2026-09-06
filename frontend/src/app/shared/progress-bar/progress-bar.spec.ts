import { TestBed } from '@angular/core/testing';
import { ProgressBar } from './progress-bar';

describe('ProgressBar', () => {
  it('valueに応じて進捗バーの幅(%)を反映する', () => {
    const fixture = TestBed.createComponent(ProgressBar);
    fixture.componentRef.setInput('value', 42);
    fixture.detectChanges();

    const fill = fixture.nativeElement.querySelector('.progress-fill') as HTMLElement;
    expect(fill.style.width).toBe('42%');
  });

  it('value=0では幅0%になる', () => {
    const fixture = TestBed.createComponent(ProgressBar);
    fixture.componentRef.setInput('value', 0);
    fixture.detectChanges();

    const fill = fixture.nativeElement.querySelector('.progress-fill') as HTMLElement;
    expect(fill.style.width).toBe('0%');
  });
});
