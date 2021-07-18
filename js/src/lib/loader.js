import AcmsSyncLoader from './sync-loader.js';
import { cacheBusting } from '../config.js';

/**
 * 名前からオブジェクトへの参照を取得する
 *
 * @param {String} name
 */
const getObjectPointerFromName = (name) => {
  const nameTokens = name.split('.');
  const iz = nameTokens.length;
  let objPointer = global;
  let i = 0;
  let token;
  let parentPointer;

  for (; i < iz; i++) {
    token = nameTokens[i];
    if (objPointer[token] === undefined) {
      objPointer[token] = {};
    }

    // 直前のポインタ（親オブジェクト）を残す
    parentPointer = objPointer;

    // ポインタを更新する
    objPointer = objPointer[token];
  }

  return {
    current: objPointer,
    token,
    parent: parentPointer
  };
};

/**
 * loadClosureFactory
 *
 * @param {String}   url        ロードするリソースのURL
 * @param {String}   [charset]  文字コード指定
 * @param {Function} [pre]      ロード前実行
 * @param {Function} [post]     ロード後実行
 */
const loadClosureFactory = (url, charset, pre, post, loaded) => {
  url = (typeof (url) === 'string') ? url : '';
  url += cacheBusting;
  charset = (typeof (charset) === 'string') ? charset : '';
  pre = $.isFunction(pre) ? pre : function () {};
  post = $.isFunction(post) ? post : function () {};
  loaded = $.isFunction(loaded) ? loaded : function () {};

  /**
   * loadClosure
   */
  return function (callSpecifiedFunction) {
    if (!$.isFunction(callSpecifiedFunction)) {
      callSpecifiedFunction = function () {};
    }
    const self = arguments.callee;

    if (!self.executed) {
      self.executed = true;
      self.stack = [callSpecifiedFunction];

      new AcmsSyncLoader()
        .next(pre)
        .next(url)
        .next(loaded)
        .next(() => {
          while (self.stack.length) {
            (self.stack.shift())();
          }
        })
        .load(() => {
          post();
          ACMS.dispatchEvent(`${url.match('.+/(.+?)\.[a-z]+([\?#;].*)?$')[1]}Ready`);
        });
    } else {
      self.stack.push(callSpecifiedFunction);
      return true;
    }
  };
};

/**
 * assignLoadClosure
 *
 * @param {String}   name        オブジェクト名
 * @param {Function} loadClosure スクリプトロードのクロージャ
 * @param {Boolean}  [del]       削除フラグ
 */
const assignLoadClosure = (name, loadClosure, del) => {
  const pointerInfo = getObjectPointerFromName(name);
  const parentPointer = pointerInfo.parent;
  const token = pointerInfo.token;

  //-------
  // proxy
  //
  // 変数にするとただの参照代入になるのでプロパティとしてアクセス
  // @example
  // ACMS.Dispatch['Edit'] = function() {...};
  parentPointer[token] = function (...args) {
    const scope = this;
    const placeholder = arguments.callee;

    if (del) {
      global[name.replace(/\..*$/, '')] = undefined;
    }

    /**
     * loadClosure <- callSpecifiedFunction
     */
    return loadClosure(() => {
      let func;

      try {
        func = getObjectPointerFromName(name).current;
      } catch (e) {
        return false;
      }

      if (typeof (func) !== 'function') {
        return false;
      }
      if (func === placeholder) {
        return false;
      }

      //--------------------
      // take over property
      let key;
      let _key;

      for (key in placeholder) {
        if (func[key]) {
          for (_key in placeholder[key]) {
            func[key][_key] = placeholder[key][_key];
          }
        } else {
          func[key] = placeholder[key];
        }
      }
      return func.apply(scope, args);
    });
  };
};

/**
 * cssのロード
 *
 * @param url
 * @param charset
 * @param prepend
 * @returns {boolean}
 */
const loadClosureFactoryCss = (url, charset, prepend) => {
  if (!url) {
    return false;
  }
  url += cacheBusting;

  const link = window.document.createElement('link');
  link.type = 'text/css';
  link.rel = 'stylesheet';
  if (charset) {
    link.charset = charset;
  }
  link.href = url;

  const head = window.document.getElementsByTagName('head')[0];

  if (prepend && head.firstChild) {
    head.insertBefore(link, head.firstChild);
  } else {
    head.appendChild(link);
  }
  return true;
};

export { loadClosureFactory, loadClosureFactoryCss, assignLoadClosure };
