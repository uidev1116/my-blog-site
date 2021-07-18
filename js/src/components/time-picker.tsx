import * as React from 'react';
import flatPicker from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

type TimePickerProps = {
  value: string, 
  className: string, 
  style: React.CSSProperties, 
  onInput?: () => any
  onChange: (value: string) => any
};

export default class TimePicker extends React.Component<TimePickerProps> {

  picker: HTMLInputElement;

  static defaultProps = {
    className: 'acms-admin-form-width-mini'
  }

  constructor(props) {
    super(props);
  }

  componentDidMount() {
    flatPicker(this.picker, {
      enableTime: true,
      noCalendar: true,
      dateFormat: "H:i",
      time_24hr: true,
      onChange: (date, str) => {
        this.props.onChange(str);
      }
    });
  }

  render() {
    const { value, className, style, onInput } = this.props;
    return (<input value={value} type="text" style={style} className={className} ref={(picker) => {
      this.picker = picker;
    }} onInput={() => { if (typeof onInput === "function") {
      onInput();
    }}}/>)
  }
}