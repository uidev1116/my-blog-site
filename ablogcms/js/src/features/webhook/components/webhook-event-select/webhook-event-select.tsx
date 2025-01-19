import { useRef, useCallback, useEffect, useState } from 'react';
import { SelectInstance } from 'react-select';
import RichSelect from '../../../../components/rich-select/rich-select';
import useWebhookEventOptionsSWR from '../../hooks/use-webhook-event-options-swr';
import { WebhookEventOption } from '../../types';
import useFirstMountState from '../../../../hooks/use-first-mount-state';

interface WebhookEventSelectProps
  extends Partial<
    Pick<
      React.ComponentPropsWithoutRef<typeof RichSelect>,
      'id' | 'inputId' | 'isDisabled' | 'form' | 'name' | 'menuPortalTarget'
    >
  > {
  type?: string;
  defaultValue?: WebhookEventOption[];
  onChange?: (value: WebhookEventOption[]) => void;
}

const WebhookEventSelect = ({
  type = '',
  onChange,
  defaultValue: defaultValueProp = [],
  ...props
}: WebhookEventSelectProps) => {
  const { options, isLoading } = useWebhookEventOptionsSWR(type);

  const [value, setValue] = useState<WebhookEventOption[] | null>(null);
  const [defaultValue, setDefaultValue] = useState<WebhookEventOption[] | null>(null);

  const handleChange = useCallback(
    (newValue: readonly WebhookEventOption[]) => {
      setValue([...newValue]);
      onChange?.([...newValue]);
    },
    [onChange]
  );

  useEffect(() => {
    if (defaultValue === null && options && options.length > 0) {
      // Webhookイベントのデフォルト値を設定
      // Webhookイベントのデータ（option）は、サーバーから全権取得することが保証されている前提
      setDefaultValue(
        options.filter((option) => defaultValueProp.map((defaultOption) => defaultOption.value).includes(option.value))
      );
    }
  }, [options, defaultValue, defaultValueProp]);

  useEffect(() => {
    setValue(defaultValue);
  }, [defaultValue]);

  const ref = useRef<SelectInstance<WebhookEventOption, true>>(null);
  const isFirstMount = useFirstMountState();
  useEffect(() => {
    if (ref.current && !isFirstMount) {
      ref.current.clearValue();
    }
    // isFirstMount を監視すると、初回レンダリング時にクリアされてしまうため、監視しない
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [type]);

  if (type === '') {
    return null;
  }

  return (
    <RichSelect<WebhookEventOption, true>
      ref={ref}
      isClearable
      value={value}
      onChange={handleChange}
      options={options}
      isLoading={isLoading}
      isMulti
      closeMenuOnSelect={false}
      placeholder={ACMS.i18n('webhook.event_select_placeholder')}
      noOptionsMessage={() => ACMS.i18n('webhook.event_select_no_options_message')}
      {...props}
    />
  );
};

export default WebhookEventSelect;
