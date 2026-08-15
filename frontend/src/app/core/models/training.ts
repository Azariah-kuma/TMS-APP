import { TrainingLesson } from './training-lesson';

export interface Training {
  id: number;
  title: string;
  description: string | null;
  category: string | null;
  is_active: boolean;
  lessons?: TrainingLesson[];
}
