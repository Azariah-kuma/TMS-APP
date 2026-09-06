export interface TrainingLessonAttachment {
  id: number;
  url: string;
  original_name: string;
  mime_type: string;
}

export interface TrainingLesson {
  id: number;
  training_id: number;
  title: string;
  position: number;
  attachments: TrainingLessonAttachment[];
}
