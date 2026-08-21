import { Employee } from './employee';

export interface User {
  id: number;
  name: string;
  name_kana: string;
  last_name: string;
  first_name: string;
  last_name_kana: string;
  first_name_kana: string;
  email: string;
  employee: Employee | null;
}
