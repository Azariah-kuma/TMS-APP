export interface TrainingSummaryRow {
  id: number;
  name: string;
  not_started: number;
  in_progress: number;
  completed: number;
}

export interface TrainingSummaryReport {
  by_training: TrainingSummaryRow[];
  by_department: TrainingSummaryRow[];
}
