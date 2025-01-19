import { Suspense, lazy } from 'react';
import { render } from '../utils/react';
import { TagOption } from '../features/tag/types';
import { triggerEvent } from '../utils';

function createDefaultValue(defaultValueString: string): TagOption[] {
  const defaultValues = defaultValueString.split(',').map((value) => value.trim());
  return defaultValues
    .map((value) => ({
      label: value,
      value,
    }))
    .filter((option) => option.value && option.label);
}

export default function dispatchTagSelect(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>('.js-admin-tag-select');

  if (elements.length === 0) {
    return;
  }

  const TagSelect = lazy(
    () => import(/* webpackChunkName: "tag-select" */ '../features/tag/components/tag-select/tag-select')
  );

  function renderForLegacy(element: HTMLElement) {
    const target = element.querySelector<HTMLElement>('.js-target');
    const input = element.querySelector<HTMLInputElement>('.js-value');

    if (!target) {
      throw new Error('Not found target element!');
    }
    if (!input) {
      throw new Error('Not found input element!');
    }

    const defaultValue = createDefaultValue(input.value);

    render(
      <Suspense fallback={null}>
        <TagSelect
          defaultValue={defaultValue}
          onChange={(newValue) => {
            input.value = newValue.map((option) => option.value).join(',');
            triggerEvent(input, 'acmsAdminTagChange', { bubbles: true });
          }}
        />
      </Suspense>,
      target
    );
  }

  elements.forEach((element) => {
    const target = element.querySelector<HTMLElement>('.js-target');
    if (target) {
      return renderForLegacy(element); // 古いインターフェースに対応
    }
    const {
      target: selector,
      defaultValue: defaultValueString = '',
      id,
      inputId,
      isDisabled,
      form,
      name,
      menuPortalTarget: menuPortalTargetSelector,
    } = element.dataset;

    if (selector === undefined) {
      throw new Error('Not found data-target attribute!');
    }

    const input = context.querySelector<HTMLInputElement>(selector);

    if (!input) {
      throw new Error('Not found input element!');
    }

    const defaultValue = createDefaultValue(defaultValueString);

    render(
      <Suspense fallback={null}>
        <TagSelect
          defaultValue={defaultValue}
          onChange={(newValue) => {
            input.value = newValue.map((option) => option.value).join(',');
            triggerEvent(input, 'acmsAdminTagChange', { bubbles: true });
          }}
          id={id}
          inputId={inputId}
          isDisabled={isDisabled === 'true'}
          form={form}
          name={name}
          menuPortalTarget={
            menuPortalTargetSelector ? document.querySelector<HTMLElement>(menuPortalTargetSelector) : undefined
          }
        />
      </Suspense>,
      element
    );
  });
}
