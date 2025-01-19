import { CSSProperties, ReactNode, useEffect, useState } from 'react';
import classnames from 'classnames';
import styled from 'styled-components';
import { createPortal } from 'react-dom';

const NotifyBox = styled.div`
  position: fixed;
  inset: -70px 0 auto;
  z-index: 100100;
  display: none;
  width: 100%;
  padding: 10px;
  font-size: 13px;
  color: #fff;
  text-align: center;
  background: #5690d8;
  border-radius: 0;
  transition: top 0.4s ease-in;
  transform: translate3d(0, 0, 200px);

  &.init {
    display: block;
  }

  &.active {
    top: 0;
  }
`;

interface NotifyProps {
  onFinish: () => void;
  style?: CSSProperties;
  message: ReactNode;
  show: boolean;
  container?: HTMLElement;
}

const Notify = ({ onFinish, style, message, show, container = document.body }: NotifyProps) => {
  const [init, setInit] = useState(false);
  const [active, setActive] = useState(false);

  useEffect(() => {
    if (show) {
      setInit(true);
      const activeTimeout = setTimeout(() => setActive(true), 10);
      const inactiveTimeout = setTimeout(() => setActive(false), 1500);
      const finishTimeout = setTimeout(() => {
        setInit(false);
        if (onFinish) {
          onFinish();
        }
      }, 1800);

      return () => {
        clearTimeout(activeTimeout);
        clearTimeout(inactiveTimeout);
        clearTimeout(finishTimeout);
      };
    }
  }, [show, onFinish]);

  if (!show) {
    return null;
  }

  return createPortal(
    <NotifyBox style={style} className={classnames({ active, init })}>
      {message}
    </NotifyBox>,
    container
  );
};

export default Notify;
