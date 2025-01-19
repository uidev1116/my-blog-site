import { lazy, Suspense } from 'react';
import { render } from '../utils/react';

export default function dispatchEntryLockModal(context: Element | Document = document) {
  const element = context.querySelector('.js-acms-entry-lock');
  if (!element) {
    return;
  }

  const EntryLockModal = lazy(
    () =>
      import(
        /* webpackChunkName: "entry-lock-modal" */ '../features/entry-lock/components/entry-lock-modal/entry-lock-modal'
      )
  );
  render(
    <Suspense fallback={null}>
      <EntryLockModal />
    </Suspense>,
    element
  );
}
