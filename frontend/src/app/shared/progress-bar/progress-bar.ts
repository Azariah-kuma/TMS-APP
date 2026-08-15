import { Component, input } from '@angular/core';

@Component({
  selector: 'app-progress-bar',
  template: `
    <div class="progress-track">
      <div class="progress-fill" [style.width.%]="value()"></div>
    </div>
  `,
})
export class ProgressBar {
  readonly value = input.required<number>();
}
