export interface DepartmentBudget {
  id: number;
  department_id: number;
  department_name?: string;
  fiscal_year: number;
  budget_amount: number;
}

/** 部署別の年度予算・消費額（GET /api/reports/budget-usage）。 */
export interface BudgetUsageRow {
  department_id: number;
  department_name: string;
  fiscal_year: number;
  budget_id: number | null;
  budget_amount: number | null;
  consumed_amount: number;
  remaining_amount: number | null;
  is_over_budget: boolean;
}
