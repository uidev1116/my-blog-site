import * as React from 'react';
import styled from 'styled-components';

const SplashOuter = styled.div`
  position: absolute;
  top: 0px;
  left: 0px;
  width: 100%;
  height: 100%;
`;

const SplashInner = styled.div`
  display: table;
  width: 100%;
  height: 100%;
`;

const Splash = styled.div`
  display: table-cell;
  vertical-align: middle;
  text-align: center;
`;

const SplashFrame = styled.div`
  width: 160px;
  height: 160px;
  margin: 0 auto;
  border-radius: 10%;
  background: #000;
  opacity: .8;
  text-align: center;
  padding-top: 30px;
  box-sizing: border-box;
`;

const SplashMsg = styled.p`
  color: #FFF;
  font-size: 12px;
`;


export default ({message = ACMS.i18n('splash.loading')}) => (
  <SplashOuter>
    <SplashInner>
      <Splash>
        <SplashFrame>
          <div className="js-acms_loader_img js-acms_loader"></div> 
          <SplashMsg>{message}</SplashMsg>
        </SplashFrame>
      </Splash>
    </SplashInner>
  </SplashOuter>
);
