import { Suspense, lazy } from 'react';
import { render } from '../utils/react';

export default function dispatchPreviewMode(context: Document | Element) {
  const buttons = context.querySelectorAll<HTMLButtonElement>('.js-acms-preview-button');

  if (buttons.length === 0) {
    return;
  }

  const PreviewModal = lazy(
    () => import(/* webpackChunkName: "preview-modal" */ '../features/preview/components/preview-modal/preview-modal')
  );
  const rootDom = document.createElement('div');
  document.body.appendChild(rootDom);

  render(
    <Suspense fallback={null}>
      <PreviewModal buttons={buttons} />
    </Suspense>,
    rootDom
  );
}
