import { Suspense, lazy } from 'react';
import { render } from '../utils/react';
import { triggerEvent } from '../utils';

export default function dispatchCategorySelect(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>('.js-admin-category-select');

  if (elements.length === 0) {
    return;
  }

  const CategorySelect = lazy(
    () =>
      import(
        /* webpackChunkName: "category-select" */ '../features/category/components/category-select/category-select'
      )
  );

  function renderForLegacy(element: HTMLElement) {
    const target = element.querySelector<HTMLElement>('.js-target');
    const input = element.querySelector<HTMLInputElement>('.js-value');
    const etcInput = element.querySelectorAll<HTMLInputElement>('.js-value2');

    if (!target) {
      throw new Error('Not found target element!');
    }
    if (!input) {
      throw new Error('Not found input element!');
    }

    const inputs = [input, ...etcInput];

    const customOptions = [
      ...(element.dataset.none === 'true' ? [{ value: '0', label: ACMS.i18n('category.select_no_option_label') }] : []),
    ];

    render(
      <Suspense fallback={null}>
        <div style={{ display: 'inline-block', width: '350px' }}>
          <CategorySelect
            defaultValue={input.value ? parseInt(input.value, 10) : undefined}
            onChange={(newValue) => {
              inputs.forEach((input) => {
                input.value = newValue?.value || '';
                triggerEvent(input, 'acmsAdminCategoryChange', { bubbles: true });
              });
            }}
            narrowDown={element.getAttribute('data-narrow-down') === 'true'}
            customOptions={customOptions}
            isCreatable={element.getAttribute('data-creation') === 'true'}
          />
        </div>
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
      defaultValue = '',
      isCreatable = 'false',
      narrowDown = 'false',
      isClearable = 'true',
      noOption = 'false',
      mtOption = 'false',
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

    const inputs = context.querySelectorAll<HTMLInputElement>(selector);

    if (inputs.length === 0) {
      throw new Error('Not found input element!');
    }

    const customOptions = [
      ...(noOption === 'true' ? [{ value: '0', label: ACMS.i18n('category.select_no_option_label') }] : []),
      ...(mtOption === 'true' ? [{ value: '-1', label: ACMS.i18n('category.select_mt_option_label') }] : []),
    ].sort((a, b) => a.value.localeCompare(b.value));

    render(
      <Suspense fallback={null}>
        <CategorySelect
          defaultValue={defaultValue ? parseInt(defaultValue, 10) : undefined}
          onChange={(newValue) => {
            inputs.forEach((input) => {
              input.value = newValue?.value || '';
              triggerEvent(input, 'acmsAdminCategoryChange', { bubbles: true });
            });
          }}
          narrowDown={narrowDown === 'true'}
          isCreatable={isCreatable === 'true'}
          isClearable={isClearable === 'true'}
          customOptions={customOptions}
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
