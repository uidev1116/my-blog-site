import * as React from 'react';
import flatPicker from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import "flatpickr/dist/l10n/ja";

type TimePickerProps = {
  value: string,
  className: string,
  style: React.CSSProperties,
  onChange(value: string): any
}

export default class TimePicker extends React.Component<TimePickerProps> {

  picker: HTMLInputElement;
  flatPicker: flatPicker.Instance;

  static defaultProps = {
    className: 'acms-admin-form-width-mini'
  }

  constructor(props) {
    super(props);
  }

  componentDidMount() {
    if (typeof ACMS === 'undefined') {
      return;
    }
    this.flatPicker = flatPicker(this.picker, {
      ...this.props,
      allowInput: true,
      locale: /^ja/.test(ACMS.i18n.lng) ? 'ja' : 'en',
      onChange: (date, str) => {
        this.props.onChange(str);
      }
    }) as flatPicker.Instance;
  }

  onInput(e) {
    this.flatPicker.jumpToDate(e.target.value);
    this.props.onChange(e.target.value);
  }

  render() {
    const { value, className, style } = this.props;
    return (<input value={value} style={style} onInput={this.onInput.bind(this)} type="text" className={className} ref={(picker) => {
      this.picker = picker;
    }} />)
  }
}