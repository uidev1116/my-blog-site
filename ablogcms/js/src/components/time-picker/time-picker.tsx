import { useEffect, useRef, forwardRef, useState } from 'react';
import flatPicker from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/l10n/ja';
import useEffectOnce from '../../hooks/use-effect-once';
import useMergeRefs from '../../hooks/use-merge-refs';

interface TimePickerProps
  extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'onChange' | 'value' | 'defaultValue'> {
  value?: string;
  defaultValue?: string;
  onChange?: flatPicker.Options.Options['onChange'];
  options?: flatPicker.Options.Options;
}

const defaultOptions: flatPicker.Options.Options = {
  enableTime: true,
  enableSeconds: true,
  noCalendar: true,
  dateFormat: 'H:i:S',
  time_24hr: true,
};

const TimePicker = forwardRef<HTMLInputElement, TimePickerProps>(
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

    return <input type="text" {...inputProps} className={className} ref={useMergeRefs(nodeRef, ref)} />;
  }
);

TimePicker.displayName = 'TimePicker';

export default TimePicker;
