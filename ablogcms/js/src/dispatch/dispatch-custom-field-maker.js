import { lazy, Suspense } from 'react';
import { render } from '../utils/react';

export default function dispatchCustomFieldMaker(context = document) {
  const element = context.querySelector('#custom-field-maker');
  if (!element) {
    return;
  }

  import(/* webpackChunkName: "custom-field-maker-css" */ 'custom-field-maker/css/custom-field-maker.css');
  const CustomFieldMaker = lazy(() => import(/* webpackChunkName: "custom-field-maker" */ 'custom-field-maker'));
  render(
    <Suspense fallback={null}>
      <CustomFieldMaker />
    </Suspense>,
    element
  );
}
