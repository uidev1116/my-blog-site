import * as React from 'react';

const style = {
  fontSize: "16px",
  padding: "5px",
  color: "#333",
  width: '150px',
  lineHeight: "1",
  verticalAlign: "middle",
  background: "#fbfbfb",
  border: "1px solid rgba(0,0,0,.2)",
  borderRadius: "2px",
  boxShadow: "inset 0 1px 1px rgba(0,0,0,.1)",
  transition: "background-color .2s"
};

export default class EditableLabel extends React.Component {

  static defaultProps = {
    onChange: () => {},
    placeholder: '',
    autoFocus: false
  }

  constructor(props) {
    super(props);
    this.state = {
      text: props.defaultValue
    }
  }


  getText = el => {
    return el.innerText
  }

  onTextChange = ev => {
    const text = this.getText(ev.target)
    this.setState({text: text})
    this.props.onChange(this.state.text)
  }

  componentDidMount() {
    if (this.props.autoFocus) {
      this.refDiv.focus()
    }
  }

  onBlur = () => {
    this.props.onChange(this.state.text)
  }

  onPaste = ev => {
    ev.preventDefault()
    const text = ev.clipboardData.getData('text')
    document.execCommand('insertText', false, text)
  }

  getClassName = () => {
    const placeholder = this.state.text === '' ? 'comPlainTextContentEditable--has-placeholder' : ''
    return `comPlainTextContentEditable ${placeholder}`
  }

  render() {
    return (
      <div
        style={style}
        ref={ref => (this.refDiv = ref)}
        contentEditable
        className={this.getClassName()}
        onPaste={this.onPaste}
        onBlur={this.onBlur}
        onInput={this.onTextChange}
        placeholder={this.props.placeholder}
      />
    )
  }
}
