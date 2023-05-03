import React, { Component } from 'react';
import { SketchPicker } from 'react-color';
import { findAncestor } from '../lib/dom';

export default class ColorPicker extends Component {
  static defaultProps = {
    defaultColor: '#ffffff',
    button: null,
    width: '170px',
    colors: ['#D9E3F0', '#F47373', '#697689', '#37D67A', '#2CCCE4', '#555555', '#dce775', '#ff8a65', '#ba68c8'],
    handleChangeColor: () => {},
  };

  constructor(props) {
    super(props);

    this.state = {
      showPicker: false,
    };
  }

  componentDidMount() {
    const { button } = this.props;
    if (button) {
      button.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        this.setState({
          showPicker: true,
        });
      });
    }

    document.addEventListener('click', (e) => {
      if (!findAncestor(e.target, '.js-acms-color-picker-box')) {
        this.handleCloseModal();
      }
    });
  }

  handleCloseModal() {
    const { showPicker } = this.state;
    if (!showPicker) {
      return;
    }
    this.setState({
      showPicker: false,
    });
  }

  handleChangeComplete(color) {
    const { handleChangeColor } = this.props;
    handleChangeColor(color);
  }

  render() {
    const { showPicker } = this.state;

    const {
      defaultColor, colors, width, style,
    } = this.props;

    const display = showPicker ? 'block' : 'none';

    return (
      <div // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-noninteractive-element-interactions
        className="js-acms-color-picker-box"
        style={{ ...style, display }}
      >
        <SketchPicker
          color={defaultColor}
          presetColors={colors}
          width={width}
          onChangeComplete={(color) => {
            this.handleChangeComplete(color);
          }}
        />
      </div>
    );
  }
}
