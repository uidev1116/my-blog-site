import { lazy, Suspense } from 'react';
import { render } from '../utils/react';

export default function dispatchQuickSearch(context = document) {
  const preview = document.querySelector('.js-acms-preview');
  if (preview) {
    return;
  }
  const buttons = context.querySelectorAll('.js-search-everything');

  if (ACMS.Dispatch.Utility.browser().ltIE9 || ACMS.Config.auth === 'subscriber') {
    [].forEach.call(buttons, (button) => {
      button.parentNode.removeChild(button);
    });
  }

  if (ACMS.Config.quickSearchFeature !== true || /(\?|&)acms-preview-mode=/.test(location.href)) {
    return;
  }

  const QuickSearch = lazy(
    () => import(/* webpackChunkName: "quick-search" */ '../features/quick-search/components/quick-search/quick-search')
  );

  const rootDom = document.createElement('div');
  rootDom.id = 'acms-search-everything';
  document.body.appendChild(rootDom);
  return render(
    <Suspense fallback={null}>
      <QuickSearch buttons={buttons} />
    </Suspense>,
    rootDom
  );
}
