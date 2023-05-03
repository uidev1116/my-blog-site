import { removeClass, addClass, findAncestor } from './dom';

const twitterPopup = (type: string) => `
  <div class="acms-admin-modal" role="dialog" style="background-color: rgba(0, 0, 0, 0.5); display: block;">
    <div class="acms-admin-modal-dialog" style="max-width: 360px; margin-top: 100px;">
      <div class="acms-admin-modal-content">
        <div class="acms-admin-modal-header">
          <div class="acms-admin-title-wrapper">
            <h3 class="acms-admin-modal-heading">Twitter ログイン認証画面</h3>
          </div>
        </div>
        <div class="adcms-admin-modal-body">
          <div class="acms-admin-padding-small acms-admin-form">
            <h2>PINコードを取得して、認証してください。</h2>
            <p class="js-before-pin">
              ${type ? `<input type="hidden" name="type" value="${type}" />` : ''}
              <button name="ACMS_POST_Api_Twitter_OAuth_Login" class="js-pin acms-admin-btn acms-admin-btn-large acms-admin-btn-flat-primary acms-admin-btn-block">Twitter認証画面へ</button>
            </p>
            <div class="js-after-pin" style="display:none">
              <p>取得したPINコードを以下の入力欄に記入し、認証してください。</p>
              <input type="text" class="acms-admin-form-width-full js-twitter-pin" name="oauth_verifier"/>
              <div class="acms-admin-margin-top-small">
                <input type="submit" class="acms-admin-btn acms-admin-btn-large acms-admin-btn-flat-primary acms-admin-btn-block" name="ACMS_POST_Api_Twitter_OAuth_LoginWithPin" value="認証">
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
`;

const requestWait = () => new Promise<void>((resolve) => {
  requestAnimationFrame(() => {
    resolve();
  });
});

const wait = (time: number) => new Promise<void>((resolve) => {
  setTimeout(() => {
    resolve();
  }, time);
});

export default (btn: HTMLButtonElement, type = '') => {
  btn.addEventListener('click', async () => {
    const form = findAncestor(btn, 'form');
    form.setAttribute('target', '_blank');
    const div = document.createElement('div');
    div.innerHTML = twitterPopup(type);
    const parent = findAncestor(btn, '.js-twitter-login-parent');
    parent.appendChild(div);
    const modal = div.firstElementChild as HTMLElement;
    const pinBtn = div.querySelector('.js-pin');
    const beforePin = div.querySelector<HTMLDivElement>('.js-before-pin');
    const afterPin = div.querySelector<HTMLDivElement>('.js-after-pin');
    await requestWait();
    modal.style.display = 'block';
    await requestWait();
    addClass(modal, 'in');
    wait(500);
    const removeModal = async () => {
      form.removeAttribute('target');
      removeClass(modal, 'in');
      addClass(modal, 'out');
      await wait(500);
      parent.removeChild(div);
    };
    const onPinClick = () => {
      setTimeout(() => {
        if (beforePin) {
          beforePin.style.display = 'none';
        }
        if (afterPin) {
          afterPin.style.display = 'block';
        }
        form.removeAttribute('target');
      }, 1000);
    };

    const onModalClick = (e) => {
      if (e.target === modal) {
        removeModal();
      }
      modal.removeEventListener('click', onModalClick);
      pinBtn?.removeEventListener('click', onPinClick);
    };

    modal.addEventListener('click', onModalClick);
    pinBtn?.addEventListener('click', onPinClick);
  });
};
