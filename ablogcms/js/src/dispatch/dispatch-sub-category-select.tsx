import { lazy, Suspense, useEffect, useState } from 'react';
import { render } from '../utils/react';
import { SubCategoryOption } from '../features/sub-category/types';
import { triggerEvent } from '../utils';

function createDefaultValue(defaultValueString: string, defaultLabelString: string): SubCategoryOption[] {
  const defaultValues = defaultValueString.split(',').map((value) => value.trim());
  const defaultLabels = defaultLabelString.split(',').map((value) => value.trim());
  return defaultValues
    .map((value, index) => ({
      label: defaultLabels[index],
      value,
    }))
    .filter((option) => option.value && option.label);
}

export default function dispatchSubCategorySelect(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>('.js-admin-sub-category-select');

  if (elements.length === 0) {
    return;
  }

  const SubCategorySelect = lazy(
    () =>
      import(
        /* webpackChunkName: "sub-category-select" */ '../features/sub-category/components/sub-category-select/sub-category-select'
      )
  );

  const LegacyRenderer = ({ element }: { element: HTMLElement }) => {
    const target = element.querySelector<HTMLElement>('.js-target');
    const input = element.querySelector<HTMLInputElement>('.js-value');

    if (!target) {
      throw new Error('Not found target element!');
    }
    if (!input) {
      throw new Error('Not found input element!');
    }

    const categoryInput = document.querySelector<HTMLInputElement>('[name=category_id]');

    const [categoryId, setCategoryId] = useState(parseInt(categoryInput?.value || '0', 10));

    useEffect(() => {
      function handleChange(event: Event) {
        if (!(event.target instanceof HTMLInputElement)) {
          return;
        }
        setCategoryId(parseInt(event.target.value, 10));
      }

      categoryInput?.addEventListener('acmsAdminCategoryChange', handleChange);

      return () => {
        categoryInput?.removeEventListener('acmsAdminCategoryChange', handleChange);
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const defaultValue = createDefaultValue(input.value, input.dataset.label || '');

    return (
      <Suspense fallback={null}>
        <SubCategorySelect
          mainCategoryId={categoryId}
          defaultValue={defaultValue}
          onChange={(newValue) => {
            input.value = newValue.map((option) => option.value).join(',');
            triggerEvent(input, 'acmsAdminSubCategoryChange', { bubbles: true });
          }}
        />
      </Suspense>
    );
  };

  const Renderer = ({ element }: { element: HTMLElement }) => {
    const {
      target: selector,
      defaultValue: defaultValueString = '',
      defaultLabel: defaultLabelString = '',
      categoryId: idOrSelector = '',
      id,
      inputId,
      isDisabled,
      form,
      name,
      menuPortalTarget: menuPortalTargetSelector,
      limit: defaultLimit = -1,
    } = element.dataset;

    if (selector === undefined) {
      throw new Error('Not found data-target attribute!');
    }

    const input = context.querySelector<HTMLInputElement>(selector);

    if (!input) {
      throw new Error('Not found input element!');
    }

    const categoryInput = context.querySelector<HTMLInputElement>(idOrSelector);

    const [categoryId, setCategoryId] = useState(parseInt(categoryInput ? categoryInput.value : idOrSelector, 10));

    useEffect(() => {
      function handleChange(event: Event) {
        if (!(event.target instanceof HTMLInputElement)) {
          return;
        }
        setCategoryId(parseInt(event.target.value, 10));
      }

      categoryInput?.addEventListener('acmsAdminCategoryChange', handleChange);

      return () => {
        categoryInput?.removeEventListener('acmsAdminCategoryChange', handleChange);
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const defaultValue = createDefaultValue(defaultValueString, defaultLabelString);

    const isValidLimit = (value: unknown): value is number => {
      const num = typeof value === 'string' ? Number(value) : value;
      return typeof num === 'number' && !isNaN(num) && isFinite(num) && num >= 0;
    };

    return (
      <Suspense fallback={null}>
        <SubCategorySelect
          mainCategoryId={categoryId}
          defaultValue={defaultValue}
          onChange={(newValue) => {
            input.value = newValue.map((option) => option.value).join(',');
            triggerEvent(input, 'acmsAdminSubCategoryChange', { bubbles: true });
          }}
          id={id}
          inputId={inputId}
          isDisabled={isDisabled === 'true'}
          {...(isValidLimit(defaultLimit) && {
            limit: defaultLimit,
          })}
          form={form}
          name={name}
          menuPortalTarget={
            menuPortalTargetSelector ? document.querySelector<HTMLElement>(menuPortalTargetSelector) : undefined
          }
        />
      </Suspense>
    );
  };

  elements.forEach((element) => {
    const target = element.querySelector<HTMLElement>('.js-target');
    if (target) {
      return render(<LegacyRenderer element={element} />, target);
    }
    render(<Renderer element={element} />, element);
  });
}
