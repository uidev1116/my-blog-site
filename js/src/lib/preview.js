import './polyfill';
import React from 'react';
import { render } from 'react-dom';
import Preview from '../components/preview';

export default () => {
  /**
   * プレビュー
   */
  const dispatchPreviewMode = (target, url, hasCloseBtn = true, hasShareBtn = true, defaultDevice = '', ready = () => {}) => {
    if (!target) {
      document.body.insertAdjacentHTML('beforeend', '<div id="acms-preview-area"></div>');
      target = document.querySelector('#acms-preview-area');
    }
    const htmlElement = document.querySelector('html');
    htmlElement.style.overflow = 'hidden';
    render(<Preview
      src={url}
      timemachine={false}
      ruleList={[]}
      hasCloseBtn={hasCloseBtn}
      hasShareBtn={hasShareBtn}
      defaultDevice={defaultDevice}
      onClose={() => {
        $(target).fadeOut('slow').queue(() => {
          target.parentNode.removeChild(target);
          htmlElement.style.overflow = '';
        });
      }}
      ready={ready}
    />, target);
  };

  /**
   * タイムマシーン
   */
  const dispatchTimemachineMode = () => {
    let area = document.querySelector('#acms-timemachine-area');
    if (!area) {
      document.body.insertAdjacentHTML('beforeend', '<div id="acms-timemachine-area" style="position: fixed; top: 0; bottom: 0; left: 0; right: 0; z-index: 999;"></div>');
      area = document.querySelector('#acms-timemachine-area');
    }
    const htmlElement = document.querySelector('html');
    htmlElement.style.overflow = 'hidden';
    const fd = new FormData();
    fd.append('ACMS_POST_Timemachine_RuleSelectJson', true);
    fd.append('formToken', window.csrfToken);

    $.ajax({
      url: ACMS.Library.acmsLink({
        bid: ACMS.Config.bid
      }),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false
    }).then((ruleList) => {
      render(<Preview
        timemachine
        ruleList={ruleList}
        hasCloseBtn
        defaultDevice={ACMS.Config.timemachinePreviewDefaultDevice}
        onClose={() => {
          $(area).fadeOut('slow').queue(() => {
            area.parentNode.removeChild(area);
            htmlElement.style.overflow = '';
          });
        }}
      />, area);
    });
  };

  ACMS.Ready(() => {
    const previewTarget = document.querySelector('.js-acms-preview');
    const timemachineTarget = document.querySelector('.js-acms-timemachine');
    const previewButtons = document.querySelectorAll('.js-acms-preview-button');
    // IE9以下の時は、タイムマシーン機能とプレビュー機能は使えないように調整
    if (ACMS.Dispatch.Utility.browser().ltIE9) {
      [].forEach.call(previewButtons, (button) => {
        button.style.display = 'none';
      });
      timemachineTarget.style.display = 'none';
      return;
    }
    if (previewTarget) {
      const hasShareBtn = previewTarget.getAttribute('data-share') !== '0';
      const defaultDevice = previewTarget.getAttribute('data-default-device') || '';
      /**
       * preview mode
       */
      dispatchPreviewMode(previewTarget, previewTarget.getAttribute('data-url'), false, hasShareBtn, defaultDevice);
    } else {
      if (previewButtons.length > 0) {
        /**
         * preview mode event
         */
        [].forEach.call(previewButtons, (button) => {
          const defaultDevice = button.getAttribute('data-default-device') || '';
          button.addEventListener('click', () => {
            dispatchPreviewMode(null, button.getAttribute('data-url'), true, true, defaultDevice);
          });
        });
      }
      if (!window.frameElement) {
        /**
         * timemachine mode
         */
        if (timemachineTarget) {
          timemachineTarget.addEventListener('click', dispatchTimemachineMode);
        }
        /**
         * timemachine mode event
         */
        if (ACMS.Config.timeMachineMode) {
          dispatchTimemachineMode();
        }
      }
    }
  });
};
