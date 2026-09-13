import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { EmployeeService } from '../../../core/services/employee.service';
import { Employee } from '../../../core/models/employee';

@Component({
  selector: 'app-employee-list',
  imports: [RouterLink],
  templateUrl: './employee-list.html',
})
export class EmployeeList implements OnInit {
  private readonly service = inject(EmployeeService);
  private readonly auth = inject(AuthService);

  readonly isHr = this.auth.isHr;
  readonly employees = signal<Employee[]>([]);
  readonly loading = signal(true);
  readonly withRetired = signal(false);

  ngOnInit(): void {
    this.load();
  }

  toggleWithRetired(withRetired: boolean): void {
    this.withRetired.set(withRetired);
    this.load();
  }

  private load(): void {
    this.loading.set(true);

    this.service.list(this.withRetired()).subscribe((employees) => {
      this.employees.set(employees);
      this.loading.set(false);
    });
  }
}
