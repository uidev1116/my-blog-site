import useSWR from 'swr';
import { fetchTimeMachineRules } from '../api';
import type { RuleType } from '../types';

export default function useTimeMachineRulesSWR(shouldFetch: boolean) {
  const {
    data: rules,
    error,
    isLoading,
  } = useSWR<RuleType[]>(shouldFetch ? '/api/timemachine/rules' : null, fetchTimeMachineRules);

  return {
    rules,
    isLoading,
    error,
  };
}
