import { lazy, Suspense } from 'react';
import { render } from '../utils/react';

export default function dispatchAuditLogDetailModal(context: Element | Document = document) {
  const element = context.querySelector('.js-acms-audit-log');
  if (!element) {
    return;
  }
  const buttons = context.querySelectorAll<HTMLButtonElement>('.js-logger-show-detail');

  const AuditLogDetailModal = lazy(
    () =>
      import(
        /* webpackChunkName: "audit-log-detail-modal" */ '../features/audit-log/components/audit-log-detail-modal/audit-log-detail-modal'
      )
  );
  render(
    <Suspense fallback={null}>
      <AuditLogDetailModal buttons={buttons} />
    </Suspense>,
    element
  );
}
