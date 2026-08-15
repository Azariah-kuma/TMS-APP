import { Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-dashboard',
  imports: [RouterLink],
  templateUrl: './dashboard.html',
})
export class Dashboard {
  private readonly auth = inject(AuthService);

  readonly user = this.auth.currentUser;
  readonly employee = this.auth.currentEmployee;
  readonly isHr = this.auth.isHr;
}
