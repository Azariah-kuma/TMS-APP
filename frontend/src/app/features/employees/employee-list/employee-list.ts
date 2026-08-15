import { Component, inject, OnInit, signal } from '@angular/core';
import { RouterLink } from '@angular/router';
import { EmployeeService } from '../../../core/services/employee.service';
import { Employee } from '../../../core/models/employee';

@Component({
  selector: 'app-employee-list',
  imports: [RouterLink],
  templateUrl: './employee-list.html',
})
export class EmployeeList implements OnInit {
  private readonly service = inject(EmployeeService);

  readonly employees = signal<Employee[]>([]);
  readonly loading = signal(true);

  ngOnInit(): void {
    this.service.list().subscribe((employees) => {
      this.employees.set(employees);
      this.loading.set(false);
    });
  }
}
