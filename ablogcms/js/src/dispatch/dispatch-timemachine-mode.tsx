import { Suspense, lazy } from 'react';
import { render } from '../utils/react';

export default function dispatchTimeMachineMode(context: Document | Element) {
  const element = document.querySelector<HTMLElement>('.js-acms-preview');

  if (element) {
    return; // プレビューモードの場合は何もしない
  }
  const buttons = context.querySelectorAll<HTMLButtonElement>('.js-acms-timemachine');

  if (buttons.length === 0) {
    return;
  }

  const TimeMachineModal = lazy(
    () =>
      import(
        /* webpackChunkName: "timemachine-modal" */ '../features/timemachine/components/timemachine-modal/timemachine-modal'
      )
  );
  const rootDom = document.createElement('div');
  document.body.appendChild(rootDom);

  render(
    <Suspense fallback={null}>
      <TimeMachineModal buttons={buttons} />
    </Suspense>,
    rootDom
  );
}
