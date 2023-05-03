import styled, { keyframes } from 'styled-components';

const spinnerAnimation = keyframes`
  0% {
    opacity: .4;
    transform: rotate(0deg);
  }
  50% {
    opacity: 1;
    transform: rotate(180deg);
  }
  100% {
    opacity: .4;
    transform: rotate(360deg);
  }
`;

export default styled.div < { size: number } >`
  position: absolute;
  z-index: 101;
  top: 50%;
  left: 50%;
  ${(props) => `
  width: ${props.size || 30}px;
  height: ${props.size || 30}px;
  `}
  margin-top: -15px;
  margin-left: -15px;
  border: 8px solid #333;
  border-right-color: transparent;
  border-radius: 50%;
  animation: ${spinnerAnimation} 0.5s infinite linear;
`;
