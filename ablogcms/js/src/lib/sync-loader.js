/**
 * @typedef {Object} SyncLoaderOptions
 * @property {string} [charset] - Character set to use for loading scripts.
 * @property {string} [type] - The type attribute for script tags, default is 'text/javascript'.
 * @property {Function} [error] - Callback function to handle errors.
 */

/**
 * @typedef {Object} SyncLoaderQueue
 * @property {string[]} srcAry - Array of source URLs to be loaded.
 * @property {Array<Promise<any>>} asyncAry - Array of promises representing async tasks.
 * @property {string[]} loaded - Array of loaded source URLs.
 * @property {Function} add - Method to add a source URL to the queue.
 */

export default class SyncLoader {
  /**
   * constructor
   *
   * @param {SyncLoaderOptions} [options={}] - Options for configuring the SyncLoader instance.
   */
  constructor(options = {}) {
    this.charset = options.charset;
    this.type = options.type || 'text/javascript';
    this.error = options.error || function () {};

    /** @type {SyncLoaderQueue} */
    this.queue = {
      srcAry: [],
      asyncAry: [],
      loaded: [],
      add(src) {
        this.srcAry.push(src);
      },
    };
  }

  /**
   * Promiseのthenのようなもの
   *
   * @param {string|Function} arg - The source URL or a function to execute.
   * @param {boolean} async - Whether to load the resource asynchronously.
   * @param {Function} [callback] - Callback function to execute after loading.
   * @returns {this} - Returns the instance for method chaining.
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
   * @param {Function} [callback] - Callback function to execute after all resources are loaded.
   */
  load(callback) {
    this.complete = callback || function () {};
    this.assign();
  }

  /**
   * 次の要素取得
   *
   * @returns {boolean} - Returns false if no more elements to process.
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
   * @param {Function} callback - The function to execute.
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
   * @param {string} src - The source URL to load.
   * @param {boolean} [async=false] - Whether to load the resource asynchronously.
   * @param {Function} [callback] - Callback function to execute after loading.
   * @private
   */
  _load(src, async = false, callback = () => {}) {
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
      if (!tag.readyState || tag.readyState === 'loaded' || tag.readyState === 'complete') {
        tag.onreadystatechange = null;
        tag.onload();
      }
    };
  }

  /**
   * キューのチェック
   *
   * @param {string} src - The source URL to check.
   * @returns {boolean} - Returns true if the source URL is already loaded.
   * @private
   */
  _exists(src) {
    return this.queue.loaded.includes(src);
  }
}
