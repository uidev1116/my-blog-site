import ResizeImage from './lib/resize-image/util';

const parseQuery = (query: string): { [key: string]: string } => {
  const s = query.split('&');
  const data = {};
  const iz = s.length;
  let i = 0;
  let param;
  let key;
  let value;

  for (; i < iz; i++) {
    param = s[i].split('=');
    if (param[0] !== undefined) {
      key = param[0]; // eslint-disable-line prefer-destructuring
      value = param[1] !== undefined ? param.slice(1).join('=') : key;
      try {
        data[key] = decodeURIComponent(value);
      } catch (e) {
        console.log(e); // eslint-disable-line no-console
      }
    }
  }
  return data;
};

const getParameterByName = (name: string, query: string): string => {
  const search = query || location.search;
  name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]'); // eslint-disable-line no-useless-escape
  const regex = new RegExp(`[\\?&]${name}=([^&#]*)`);
  const results = regex.exec(search);
  try {
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
  } catch (e) {
    return '';
  }
};

const addListener = (
  name: string,
  listener: EventListener,
  dom: HTMLElement | HTMLDocument,
): void => {
  dom = dom || document;

  // non-IE
  if (dom.addEventListener) {
    dom.addEventListener(name, listener, false);
    // IE8
  } else if (dom.attachEvent) {
    dom.documentElement[name] = 0;
    dom.documentElement.attachEvent('onpropertychange', function (event) {
      if (event.propertyName === name) {
        listener();
        // eslint-disable-next-line no-restricted-properties
        dom.documentElement.detachEvent('onpropertychange', arguments.callee);
      }
    });
  }
};

const dispatchEvent = (name, obj, dom) => {
  obj = obj || {};
  dom = dom || document;

  // non-IE
  if (document.createEvent) {
    const evn = document.createEvent('HTMLEvents');
    evn.obj = obj;
    evn.initEvent(name, true, false);
    dom.dispatchEvent(evn);
    // IE
  } else if (dom.createEventObject) {
    dom.documentElement[name]++;
  }
};

const PerfectFormData = (dom: HTMLFormElement, dataUrlClass: string): FormData => {
  const clone = <HTMLFormElement>dom.cloneNode(true);

  // セレクトボックスの入力値の登録(cloneのbug対応)
  const selectors = dom.querySelectorAll('select');
  const cloneSelectors = clone.querySelectorAll('select');
  [].forEach.call(selectors, (select, i) => {
    [].forEach.call(select.options, (option, j) => {
      cloneSelectors[i].options[j].selected = select.options[j].selected;
    });
  });

  // input要素の削除とdataUrlの削除
  const inputs = clone.querySelectorAll('input');
  [].forEach.call(inputs, (input) => {
    if (input.getAttribute('type') === 'file' || input.classList.contains(dataUrlClass)) {
      input.remove();
    }
  });

  // DOMからFromDataを作成
  const formData = new FormData(clone);
  const postModule = <HTMLInputElement>dom.querySelector('[name^=ACMS_POST_]');
  formData.append(postModule.getAttribute('name'), postModule.value);

  // ファイル要素を追加
  const inputs2 = dom.querySelectorAll('input');
  [].forEach.call(inputs2, (input) => {
    if (input.getAttribute('type') === 'file') {
      const name = input.getAttribute('name');
      if (input.files.length > 0) {
        [].forEach.call(input.files, (file) => {
          formData.append(name, file);
        });
      } else {
        formData.append(name, new File([''], ''));
      }
    }
  });

  // dataUrlをblobに変換してFormDataに追加
  const base64Data = dom.querySelectorAll(`.${dataUrlClass}`);
  [].forEach.call(base64Data, (item) => {
    const dataUrl = item.value;
    if (!dataUrl) return;
    const name = item.getAttribute('name');
    const resizeImage = new ResizeImage();
    const blob = resizeImage.dataUrlToBlob(dataUrl);
    if (blob) {
      formData.append(name, blob);
    }
  });

  return formData;
};

const getScrollTop = () => window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

const getScrollLeft = () => window.pageXOffset || document.documentElement.scrollLeft || document.body.scrollLeft || 0;

const getOffset = (el) => {
  const rect = el.getBoundingClientRect();
  return {
    top: rect.top + getScrollTop(),
    left: rect.left + getScrollLeft(),
  };
};

/**
 * RFC 3986 に基づいてURLエンコードを行う
 * https://www.rfc-editor.org/rfc/rfc3986
 */
const encodeUri = (str: string): string => encodeURIComponent(str).replace(
  /[!'()*]/g,
  (match) => `%${match.charCodeAt(0).toString(16)}`,
);

export {
  parseQuery,
  getParameterByName,
  addListener,
  dispatchEvent,
  PerfectFormData,
  getOffset,
  encodeUri,
};
