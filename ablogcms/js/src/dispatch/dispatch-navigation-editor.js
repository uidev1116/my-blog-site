import { lazy, Suspense, StrictMode } from 'react';
import { render } from '../utils/react';

export default async function dispatchNavigationEditor(context = document) {
  const element = context.querySelector(ACMS.Config.navigationEditMark);
  if (!element) {
    return;
  }
  if (/^ie/.test(ACMS.Dispatch.Utility.getBrowser())) {
    $('.js-navigation-ie').show();
    return;
  }
  $('.js-navigation-ie').remove();

  const { AllHtmlEntities: Entities } = await import(/* webpackChunkName: "html-entities" */ 'html-entities');
  const NavigationEditor = lazy(
    () =>
      import(
        /* webpackChunkName: "navigation-editor" */ '../features/navigation-editor/components/navigation-editor/navigation-editor'
      )
  );

  const json = document.querySelector('#js-navigation-json').innerHTML;
  const items = JSON.parse(json);
  const entities = new Entities();
  items.forEach((item) => {
    item.navigation_attr = entities.decode(item.navigation_attr);
    item.navigation_a_attr = entities.decode(item.navigation_a_attr);
    item.navigation_label = entities.decode(item.navigation_label);
  });
  render(
    <Suspense fallback={null}>
      <NavigationEditor items={items} message={ACMS.Config.navigationMessage} />
    </Suspense>,
    element
  );
}
