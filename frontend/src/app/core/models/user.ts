import { Employee } from './employee';

export interface User {
  id: number;
  name: string;
  email: string;
  employee: Employee | null;
}
