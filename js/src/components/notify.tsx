import React, { Component, CSSProperties, ReactNode } from 'react';
import classnames from 'classnames';
import styled from 'styled-components';

const  NotifyBox = styled.div`
  background: #5690d8;
  border-radius: 0;
  bottom: auto;
  color: #fff;
  font-size: 13px;
  left: 0;
  padding: 10px;
  position: fixed;
  text-align: center;
  right: 0;
  top: -70px;
  transition: top .4s ease-in;
  transform: translate3d(0px, 0px, 200px);
  width: 100%;
  z-index: 100100;
  display: none;
  &.init {
    display: block;
  }
  &.active {
    top: 0;
  }
`;

interface NotifyProp {
  onFinish: Function,
  style?: CSSProperties,
  message: ReactNode,
  show: boolean
}

interface NotifyState {
  show: boolean,
  active: boolean
}

export default class Notify extends Component<NotifyProp, NotifyState> {
  constructor(props) {
    super(props);
    this.state = {
      init: false,
      show: false,
      active: false
    };
  }

  componentWillReceiveProps(props) {
    const { onFinish } = this.props;
    if (props.show === true) {
      this.setState({
        init: true
      });
      setTimeout(() => {
        this.setState({
          active: true
        });
      }, 10);
      setTimeout(() => {
        this.setState({
          active: false
        });
      }, 1500);
      setTimeout(() => {
        this.setState({
          init: false
        });
        if (onFinish) {
          onFinish();
        }
      }, 1800);
    }
  }

  render() {
    const { message, style } = this.props;
    const { active, init } = this.state;
    return (<NotifyBox style={style} className={classnames({ active, init })} >{message}</NotifyBox>);
  }
}
