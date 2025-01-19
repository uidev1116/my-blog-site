import { Suspense, lazy } from 'react';
import { render } from '../utils/react';

export default function dispatchInlinePreview(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>('.js-acms-preview');

  if (elements.length === 0) {
    return;
  }

  const InlinePreview = lazy(
    () =>
      import(/* webpackChunkName: "inline-preview" */ '../features/preview/components/inline-preview/inline-preview')
  );

  elements.forEach((element) => {
    render(
      <Suspense fallback={null}>
        <InlinePreview
          src={element.dataset.url || location.href}
          hasShareBtn={element.dataset.share !== '0'} // 互換性のため === '1' ではなく !== '0' としている
          defaultDevice={element.dataset.defaultDevice}
          hasHistoryDevice={element.dataset.hasHistoryDevice === 'on'}
          historyDeviceKey={element.dataset.historyDeviceKey}
          enableNaked={element.dataset.enableNaked === '1'}
        />
      </Suspense>,
      element
    );
  });
}
