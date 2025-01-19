import { useEffect, useRef, forwardRef, useState, useCallback } from 'react';
import flatPicker from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/l10n/ja';
import useEffectOnce from '../../hooks/use-effect-once';
import useMergeRefs from '../../hooks/use-merge-refs';

interface DatePickerProps
  extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'onChange' | 'value' | 'defaultValue'> {
  value?: string;
  defaultValue?: string;
  onChange?: flatPicker.Options.Options['onChange'];
  options?: flatPicker.Options.Options;
}

const defaultOptions: flatPicker.Options.Options = {
  allowInput: true,
  locale: /^ja/.test(ACMS.i18n.lng) ? 'ja' : 'en',
};

const DatePicker = forwardRef<HTMLInputElement, DatePickerProps>(
  (
    { options = {}, className = 'acms-admin-form-width-mini', onChange, value: valueProp, defaultValue, ...inputProps },
    ref
  ) => {
    const [value, setValue] = useState<string | undefined>(valueProp ?? defaultValue);
    const nodeRef = useRef<HTMLInputElement | null>(null);
    const flatPickerRef = useRef<flatPicker.Instance | null>(null);

    useEffectOnce(() => {
      if (nodeRef.current === null) {
        return;
      }
      if (flatPickerRef.current !== null) {
        return;
      }
      flatPickerRef.current = flatPicker(nodeRef.current, {
        ...defaultOptions,
        ...options,
        defaultDate: value,
        onChange(...args) {
          setValue(args[1]);
          if (typeof onChange === 'function') {
            onChange(...args);
          } else if (Array.isArray(onChange)) {
            onChange.forEach((fn) => {
              if (typeof fn === 'function') {
                fn(...args);
              }
            });
          }
        },
      }) as flatPicker.Instance;

      return () => {
        if (flatPickerRef.current) {
          flatPickerRef.current.destroy();
          flatPickerRef.current = null;
        }
      };
    });

    useEffect(() => {
      if (flatPickerRef.current && value !== undefined) {
        flatPickerRef.current.setDate(value, false);
      }
    }, [value]);

    // inputに直接値を入力したときにflatpickrに反映させる
    const handleBulr = useCallback(
      (event: React.FocusEvent<HTMLInputElement>) => {
        if (flatPickerRef.current) {
          const _date = new Date(event.target.value);
          const date = !isNaN(_date.getTime()) ? _date : new Date(value || '');
          if (date instanceof Date && !isNaN(date.getTime())) {
            flatPickerRef.current.setDate(date, true);
          }
        }
      },
      [value]
    );

    // inputに直接値を入力したときにflatpickerのカレンダーに反映させる
    const handleChange = useCallback((event: React.ChangeEvent<HTMLInputElement>) => {
      if (flatPickerRef.current) {
        const date = new Date(event.target.value);
        if (date instanceof Date && !isNaN(date.getTime())) {
          flatPickerRef.current.jumpToDate(date);
        }
      }
    }, []);

    return (
      <input
        type="text"
        {...inputProps}
        className={className}
        ref={useMergeRefs(nodeRef, ref)}
        onBlur={handleBulr}
        onChange={handleChange}
      />
    );
  }
);

DatePicker.displayName = 'DatePicker';

export default DatePicker;
