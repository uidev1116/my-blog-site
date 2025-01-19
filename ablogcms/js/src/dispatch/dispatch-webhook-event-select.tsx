import { lazy, Suspense, useEffect, useState } from 'react';
import { render } from '../utils/react';

export default function dispatchWebhookEventSelect(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>('.js-webhook-event-select');

  if (elements.length === 0) {
    return;
  }

  const WebhookEventSelect = lazy(
    () =>
      import(
        /* webpackChunkName: "webhook-event-select" */ '../features/webhook/components/webhook-event-select/webhook-event-select'
      )
  );

  const Renderer = ({ element }: { element: HTMLElement }) => {
    const {
      target: selector,
      defaultValue: defaultValueString = '',
      defaultLabel: defaultLabelString = '',
      type: typeOrSelector = '',
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

    const select = context.querySelector<HTMLSelectElement>(typeOrSelector);

    const [webhookType, setWebhookType] = useState(select ? select.value : typeOrSelector);

    useEffect(() => {
      function handleChange(event: Event) {
        if (!(event.target instanceof HTMLSelectElement)) {
          return;
        }
        setWebhookType(event.target.value);
      }

      select?.addEventListener('change', handleChange);

      return () => {
        select?.removeEventListener('change', handleChange);
      };
      // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    useEffect(() => {
      if (webhookType === '') {
        input.value = '';
      }
    }, [webhookType, input]);

    const defaultValues = defaultValueString.split(',');
    const defaultLabels = defaultLabelString.split(',');

    const defaultValue = defaultValues
      .map((value, index) => ({
        value,
        label: defaultLabels[index],
      }))
      .filter((option) => option.value && option.label);

    return (
      <Suspense fallback={null}>
        <WebhookEventSelect
          type={webhookType}
          defaultValue={defaultValue}
          onChange={(newValue) => {
            input.value = newValue.map((option) => option.value).join(',');
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
      </Suspense>
    );
  };

  elements.forEach((element) => {
    render(<Renderer element={element} />, element);
  });
}
