export interface RuleType {
  id: number;
  label: string;
}

export interface TimeMachineState {
  date: string;
  time: string;
  ruleId: number;
}
