import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { hrGuard } from './core/guards/hr.guard';

export const routes: Routes = [
  {
    path: 'login',
    loadComponent: () => import('./features/auth/login/login').then((m) => m.Login),
  },
  {
    path: 'register',
    loadComponent: () => import('./features/auth/register/register').then((m) => m.Register),
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
        path: 'enrollments/:id',
        loadComponent: () =>
          import('./features/trainings/enrollment-detail/enrollment-detail').then(
            (m) => m.EnrollmentDetail,
          ),
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
        path: 'employees/:id',
        loadComponent: () =>
          import('./features/employees/employee-detail/employee-detail').then((m) => m.EmployeeDetail),
      },
    ],
  },
  { path: '**', redirectTo: 'dashboard' },
];
