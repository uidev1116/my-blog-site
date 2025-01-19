import type { RuleType } from '../types';

export async function enableTimeMachineMode(formData: FormData) {
  formData.append('ACMS_POST_Timemachine_Enable', 'true');
  formData.append('formToken', window.csrfToken);

  const response = await fetch(ACMS.Library.acmsLink({ bid: ACMS.Config.bid }), {
    method: 'POST',
    body: formData,
  });
  if (!response.ok) {
    throw new Error('Failed to enable time machine mode');
  }
}

export async function disableTimeMachineMode() {
  const formData = new FormData();
  formData.append('ACMS_POST_Timemachine_Disable', 'true');
  formData.append('formToken', window.csrfToken);
  const response = await fetch(ACMS.Library.acmsLink({ bid: ACMS.Config.bid }), {
    method: 'POST',
    body: formData,
  });
  if (!response.ok) {
    throw new Error('Failed to disable time machine mode');
  }
}

export async function fetchTimeMachineRules() {
  const formData = new FormData();
  formData.append('ACMS_POST_Timemachine_RuleSelectJson', 'true');
  formData.append('formToken', window.csrfToken);
  const response = await fetch(ACMS.Library.acmsLink({ bid: ACMS.Config.bid }), {
    method: 'POST',
    body: formData,
  });

  if (!response.ok) {
    throw new Error('Failed to fetch time machine rules');
  }

  return (await response.json()) as RuleType[];
}
