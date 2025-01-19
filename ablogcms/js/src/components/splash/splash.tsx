import styled from 'styled-components';

const SplashOuter = styled.div`
  position: absolute;
  top: 0;
  left: 0;

  /* #js-loading_splashのスタイルと同じ */
  z-index: 100048;
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
  text-align: center;
  vertical-align: middle;
`;

const SplashFrame = styled.div`
  box-sizing: border-box;
  width: 160px;
  height: 160px;
  padding-top: 30px;
  margin: 0 auto;
  text-align: center;
  background: #000;
  border-radius: 10%;
  opacity: 0.8;
`;

const SplashMsg = styled.p`
  font-size: 12px;
  color: #fff;
`;

export default ({ message = ACMS.i18n('splash.loading') }) => (
  <SplashOuter>
    <SplashInner>
      <Splash>
        <SplashFrame>
          <div className="js-acms_loader_img js-acms_loader" />
          <SplashMsg>{message}</SplashMsg>
        </SplashFrame>
      </Splash>
    </SplashInner>
  </SplashOuter>
);
