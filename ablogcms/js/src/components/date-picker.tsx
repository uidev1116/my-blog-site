import * as React from 'react';
import flatPicker from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/l10n/ja';

type TimePickerProps = {
  value: string;
  className?: string;
  style: React.CSSProperties;
  onChange(value: string): void;
};

export default class TimePicker extends React.Component<TimePickerProps> {
  picker: HTMLInputElement | null;

  flatPicker: flatPicker.Instance;

  static defaultProps = {
    className: 'acms-admin-form-width-mini',
  };

  componentDidMount() {
    if (typeof ACMS === 'undefined' || !this.picker) {
      return;
    }
    const { onChange } = this.props;
    this.flatPicker = flatPicker(this.picker, {
      ...this.props,
      allowInput: true,
      locale: /^ja/.test(ACMS.i18n.lng) ? 'ja' : 'en',
      onChange: (date, str) => {
        onChange(str);
      },
    }) as flatPicker.Instance;
  }

  onInput(e) {
    const { onChange } = this.props;
    this.flatPicker.jumpToDate(e.target.value);
    onChange(e.target.value);
  }

  render() {
    const { value, className, style } = this.props;
    return (
      <input
        value={value}
        style={style}
        onInput={this.onInput.bind(this)}
        type="text"
        className={className}
        ref={(picker) => {
          this.picker = picker;
        }}
      />
    );
  }
}
