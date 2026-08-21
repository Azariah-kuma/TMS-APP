export interface TrainingLesson {
  id: number;
  training_id: number;
  title: string;
  position: number;
  content_url: string | null;
  content_original_name: string | null;
  content_mime_type: string | null;
}
