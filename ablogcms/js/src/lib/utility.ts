import tooltip from './tooltip';

export const getBrowser = () => {
  const ua = window.navigator.userAgent.toLowerCase();
  const ver = window.navigator.appVersion.toLowerCase();
  let name = 'unknown';

  if (ua.indexOf('msie') !== -1) {
    if (ver.indexOf('msie 6.') !== -1) {
      name = 'ie6';
    } else if (ver.indexOf('msie 7.') !== -1) {
      name = 'ie7';
    } else if (ver.indexOf('msie 8.') !== -1) {
      name = 'ie8';
    } else if (ver.indexOf('msie 9.') !== -1) {
      name = 'ie9';
    } else if (ver.indexOf('msie 10.') !== -1) {
      name = 'ie10';
    } else {
      name = 'ie';
    }
  } else if (ua.indexOf('trident/7') !== -1) {
    name = 'ie11';
  } else if (ua.indexOf('chrome') !== -1) {
    name = 'chrome';
  } else if (ua.indexOf('safari') !== -1) {
    name = 'safari';
  } else if (ua.indexOf('opera') !== -1) {
    name = 'opera';
  } else if (ua.indexOf('firefox') !== -1) {
    name = 'firefox';
  }
  return name;
};

export const isOldIE = () => {
  const browser = getBrowser();
  if (browser.indexOf('ie') !== -1) {
    if (parseInt(browser.replace(/[^0-9]/g, ''), 10) <= 10) {
      return true;
    }
  }
  return false;
};

export const contrastColor = (hex, black = '#000000', white = '#ffffff') => {
  const rgb = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
  const a = 1 - (0.299 * parseInt(rgb[1], 16) + 0.587 * parseInt(rgb[2], 16) + 0.114 * parseInt(rgb[3], 16)) / 255;

  if (a < 0.4) {
    // bright colors - black font
    return black;
  }
  // dark colors - white font
  return white;
};

export const rgb2hex = (rgb) => {
  const rgbAry = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
  const hexDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
  if (!rgbAry) {
    return '';
  }
  return `#${[rgbAry[1], rgbAry[2], rgbAry[3]]
    .map((x) => (isNaN(x) ? '00' : hexDigits[(x - (x % 16)) / 16] + hexDigits[x % 16]))
    .join('')}`;
};

export const random = (length) => {
  if (!length) length = 8;
  const quotient = Math.floor(length / 8);
  let remainder = length % 8;
  let txt = '';
  for (let i = 0; i < quotient; i += 1) {
    txt += Math.random().toString(36).slice(-8);
  }
  if (remainder > 0) {
    remainder *= -1;
    txt += Math.random().toString(36).slice(remainder);
  }
  return txt;
};

export class ExpireLocalStorage {
  save(key, jsonData, expirationSec) {
    if (!('localStorage' in window) || !(window.localStorage !== null)) {
      return false;
    }
    const expirationMS = expirationSec * 1000;
    const record = { value: JSON.stringify(jsonData), timestamp: new Date().getTime() + expirationMS };
    localStorage.setItem(key, JSON.stringify(record));
    return jsonData;
  }

  load(key) {
    if (!('localStorage' in window) || !(window.localStorage !== null)) {
      return false;
    }
    const data = localStorage.getItem(key);
    if (!data) {
      return false;
    }
    const record = JSON.parse(data);
    if (!record) {
      return false;
    }
    return new Date().getTime() < record.timestamp && JSON.parse(record.value);
  }
}

export const getScrollTop = () => window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

export const formatBytes = (byte: string | number) => {
  const units = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
  let l = 0;
  let n = typeof byte === 'number' ? byte : parseInt(byte, 10) || 0;
  while (n >= 1024 && ++l) {
    n /= 1024;
  }
  return `${n.toFixed(n >= 10 || l < 1 ? 0 : 1)} ${units[l]}`;
};

export const getExt = (name: string): string => name.split('.').pop();

export const getFileName = (name: string): string => name.split('/').pop();

export const setDropArea = (target: HTMLElement, dropAreaMark: string, callback) => {
  const $dropArea = $(dropAreaMark, target);
  const objDropArea = $dropArea.get(0);
  let dragging = 0;

  if (objDropArea && window.File && window.FileReader) {
    $('img', target).mousedown((e) => {
      e.preventDefault();
    });
    $('img', target).mouseup((e) => {
      e.preventDefault();
    });

    // ドロップできることを表示
    if (!$('img', target).attr('src')) {
      $dropArea.addClass('drag-n-drop-hover');
      setTimeout(() => {
        $dropArea.find('.acms-admin-drop-area').fadeOut(200, () => {
          $dropArea.removeClass('drag-n-drop-hover');
          $dropArea.find('.acms-admin-drop-area').show();
        });
      }, 800);
    }
    // ドロップ時のアクションを設定
    objDropArea.addEventListener(
      'drop',
      (event) => {
        event.stopPropagation();
        event.preventDefault();
        dragging = 0;
        $(objDropArea).removeClass('drag-n-drop-hover');
        const { files } = event.dataTransfer;
        let gif = false;
        for (let i = 0; i < files.length; i++) {
          const file = files[i];
          if (file.type === 'image/gif') {
            gif = true;
            break;
          }
        }
        if (gif) {
          if (!window.confirm(ACMS.i18n('drop_select_gif_image.alert'))) {
            return false;
          }
        }
        callback(files);
      },
      false,
    );

    // ドロップエリアにいる間
    objDropArea.addEventListener(
      'dragover',
      (event) => {
        $(objDropArea).addClass('drag-n-drop-hover');
        event.stopPropagation();
        event.preventDefault();
        return false;
      },
      false,
    );

    // ドロップエリアに入った時
    objDropArea.addEventListener(
      'dragenter',
      () => {
        dragging++;
        $(objDropArea).addClass('drag-n-drop-hover');
        event.stopPropagation();
        event.preventDefault();
        return false;
      },
      false,
    );

    // ドロップエリアから出て行った時
    objDropArea.addEventListener(
      'dragleave',
      () => {
        dragging--;
        if (dragging === 0) {
          $(objDropArea).removeClass('drag-n-drop-hover');
        }
        event.stopPropagation();
        event.preventDefault();
        return false;
      },
      false,
    );
  } else {
    // ブラウザが対応していない場合の処理
  }
};

export const setTooltips = (element: HTMLElement) => {
  const tooltips = element.querySelectorAll('.js-acms-tooltip-hover');
  [].forEach.call(tooltips, (item) => {
    let interval;
    item.addEventListener('mouseenter', () => {
      tooltip(item, true);
      interval = setInterval(() => {
        if (!document.body.contains(item)) {
          $('.js-tooltip').remove();
          clearInterval(interval);
        }
      }, 300);
    });
    item.addEventListener('mouseleave', (e) => {
      clearInterval(interval);
      if ($(e.relatedTarget).hasClass('js-tooltip')) {
        const leaveFunc = (evt) => {
          if (evt.relatedTarget !== item) {
            evt.relatedTarget.removeEventListener('mouseleave', leaveFunc);
            tooltip(item, false);
          }
        };
        e.relatedTarget.addEventListener('mouseleave', leaveFunc);
      } else {
        tooltip(item, false);
      }
    });
  });
};

export const dataURItoBlob = (dataURI: string) => {
  // convert base64 to raw binary data held in a string
  // doesn't handle URLEncoded DataURIs - see SO answer #6850276 for code that does this
  const byteString = atob(dataURI.split(',')[1]);
  // separate out the mime component
  const mimeString = dataURI.split(',')[0].split(':')[1].split(';')[0];
  // write the bytes of the string to an ArrayBuffer
  const ab = new ArrayBuffer(byteString.length);
  // create a view into the buffer
  const ia = new Uint8Array(ab);
  // set the bytes of the buffer to the correct values
  for (let i = 0; i < byteString.length; i++) {
    ia[i] = byteString.charCodeAt(i);
  }
  // write the ArrayBuffer to a blob, and you're done
  return new Blob([ab], { type: mimeString });
};
