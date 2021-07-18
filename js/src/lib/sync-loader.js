export default class SyncLoader {
  /**
   * constructor
   *
   * @param options
   */
  constructor(options) {
    const opt = options || {};

    this.charset = opt.charset;
    this.type = opt.type || 'text/javascript';
    this.error = opt.error || function () {};
    this.queue = {
      srcAry: [],
      asyncAry: [],
      loaded: [],
      add(src) {
        this.srcAry.push(src);
      }
    };
  }

  /**
   * Promiseのthenのようなもの
   *
   * @param arg
   * @param async
   * @param callback
   */
  next(arg, async, callback) {
    if (async) {
      this._load(arg, true, callback);
    } else {
      this.queue.add(arg);
    }
    return this;
  }

  /**
   * ロード開始
   *
   * @param callback
   */
  load(callback) {
    this.complete = callback || function () {};
    this.assign();
  }

  /**
   * 次の要素取得
   *
   * @returns {boolean}
   */
  assign() {
    const src = this.queue.srcAry.shift();

    if (src === undefined) {
      this.complete();
      return false;
    }
    if (typeof src === 'function') {
      this._exe(src);
    } else {
      this._load(src);
    }
  }

  /**
   * 登録された処理を実行（Promise対応）
   *
   * @param callback
   * @private
   */
  _exe(callback) {
    const handler = callback || function () {};
    const result = handler(this);
    const self = this;

    if (result instanceof Promise || (result && typeof result.then === 'function')) {
      result.then(() => {
        self.assign();
      });
    } else {
      self.assign();
    }
  }

  /**
   * リソースの読み込み
   *
   * @param src
   * @param async
   * @param callback
   * @private
   */
  _load(src, async, callback) {
    const tag = document.createElement('script');
    const head = document.getElementsByTagName('script')[0];

    tag.src = src;
    tag.type = this.type;
    tag.onerror = () => {
      this.error();
    };
    if (this.charset) {
      tag.charset = this.charset;
    }

    if (async) {
      head.parentNode.insertBefore(tag, head.nextSibling);
      tag.onload = () => {
        if (!this._exists(src)) {
          this.queue.loaded.push(src);
          callback = callback || function () {};
          callback();
        }
      };
    } else {
      tag.onload = () => {
        if (!this._exists(src)) {
          this.queue.loaded.push(src);
          this.assign();
        }
      };
      head.parentNode.insertBefore(tag, head.nextSibling);
    }

    // for IE
    tag.onreadystatechange = () => {
      if (0
        || !tag.readyState
        || tag.readyState === 'loaded'
        || tag.readyState === 'complete'
      ) {
        tag.onreadystatechange = null;
        tag.onload();
      }
    };
  }

  /**
   * キューのチェック
   *
   * @param src
   * @returns {boolean}
   * @private
   */
  _exists(src) {
    let already = false;
    for (let i = 0; i < this.queue.loaded.length; i++) {
      if (this.queue.loaded[i] === src) {
        already = true;
        break;
      }
    }
    return already;
  }
}
