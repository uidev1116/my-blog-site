import * as React from 'react';
import flatPicker from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

type TimePickerProps = {
  value: string;
  className?: string;
  style: React.CSSProperties;
  onInput?: () => void;
  onChange: (value: string) => void;
};

export default class TimePicker extends React.Component<TimePickerProps> {
  picker: HTMLInputElement | null;

  static defaultProps = {
    className: 'acms-admin-form-width-mini',
  };

  componentDidMount() {
    if (!this.picker) {
      return;
    }
    flatPicker(this.picker, {
      enableTime: true,
      enableSeconds: true,
      noCalendar: true,
      dateFormat: 'H:i:S',
      time_24hr: true,
      onChange: (date, str) => {
        this.props.onChange(str);
      },
    });
  }

  render() {
    const {
      value, className, style, onInput,
    } = this.props;
    return (
      <input
        value={value}
        type="text"
        style={style}
        className={className}
        ref={(picker) => {
          this.picker = picker;
        }}
        onInput={() => {
          if (typeof onInput === 'function') {
            onInput();
          }
        }}
      />
    );
  }
}
