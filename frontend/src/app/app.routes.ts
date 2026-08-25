import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { hrGuard } from './core/guards/hr.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login').then((m) => m.Login),
  },
  {
    path: 'set-password',
    loadComponent: () =>
      import('./features/auth/set-password/set-password').then((m) => m.SetPassword),
  },
  {
    path: '',
    loadComponent: () => import('./layout/shell/shell').then((m) => m.Shell),
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'dashboard' },
      {
        path: 'dashboard',
        loadComponent: () => import('./features/dashboard/dashboard').then((m) => m.Dashboard),
      },
      {
        path: 'trainings',
        loadComponent: () =>
          import('./features/trainings/training-list/training-list').then((m) => m.TrainingList),
      },
      {
        path: 'trainings/:id',
        loadComponent: () =>
          import('./features/trainings/training-detail/training-detail').then((m) => m.TrainingDetail),
      },
      {
        path: 'enrollments',
        loadComponent: () =>
          import('./features/trainings/enrollment-list/enrollment-list').then((m) => m.EnrollmentList),
      },
      {
        path: 'training-requests',
        loadComponent: () =>
          import('./features/trainings/training-request-list/training-request-list').then(
            (m) => m.TrainingRequestList,
          ),
      },
      {
        path: 'enrollments/:id',
        loadComponent: () =>
          import('./features/trainings/enrollment-detail/enrollment-detail').then(
            (m) => m.EnrollmentDetail,
          ),
      },
      {
        path: 'reports',
        canActivate: [hrGuard],
        loadComponent: () =>
          import('./features/reports/report-dashboard/report-dashboard').then((m) => m.ReportDashboard),
      },
      {
        path: 'departments',
        canActivate: [hrGuard],
        loadComponent: () =>
          import('./features/master-data/department-list/department-list').then((m) => m.DepartmentList),
      },
      {
        path: 'positions',
        canActivate: [hrGuard],
        loadComponent: () =>
          import('./features/master-data/position-list/position-list').then((m) => m.PositionList),
      },
      {
        path: 'employees',
        canActivate: [hrGuard],
        loadComponent: () =>
          import('./features/employees/employee-list/employee-list').then((m) => m.EmployeeList),
      },
      {
        path: 'employees/new',
        canActivate: [hrGuard],
        loadComponent: () =>
          import('./features/employees/employee-onboard/employee-onboard').then(
            (m) => m.EmployeeOnboard,
          ),
      },
      {
        path: 'employees/bulk-import',
        canActivate: [hrGuard],
        loadComponent: () =>
          import('./features/employees/employee-bulk-import/employee-bulk-import').then(
            (m) => m.EmployeeBulkImport,
          ),
      },
      {
        path: 'employees/:id',
        loadComponent: () =>
          import('./features/employees/employee-detail/employee-detail').then((m) => m.EmployeeDetail),
      },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
