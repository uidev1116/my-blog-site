import { CancelToken } from 'axios'
import axiosLib from './axios'

/**
 * e.g.
 * import IncrementalSearch from './lib/incremental-search';
 * const is = new IncrementalSearch();
 * is.addRequest('selector', endpoint, callback);
 */
export default class IncrementalSearch {
  /**
   * Constructor
   *
   * @param options
   */
  constructor(options) {
    const defaults = {
      interval: 200,
      keyName: 'word',
    }
    const opt = { ...defaults, ...options }

    this.timer = null
    this.cancel = null
    this.source = {}
    this.interval = opt.interval
    this.keyName = opt.keyName
  }

  /**
   * @param selector
   * @param endpoint
   * @param callback
   */
  addRequest(selector, endpoint, callback = () => {}) {
    const input =
      typeof selector === 'string' ? document.querySelector(selector) : selector
    input.addEventListener('keyup', (e) => {
      const { value } = e.target
      e.preventDefault()
      clearTimeout(this.timer)
      this.timer = setTimeout(() => {
        if (typeof this.source.cancel === 'function') {
          this.source.cancel()
        }
        this.request(endpoint, value).then((json) => {
          if (Array.isArray(json)) {
            callback(json)
          }
        })
      }, this.interval)
    })
  }

  /**
   * @param endpoint
   * @param value
   * @return Promise
   */
  request(endpoint, value) {
    const params = new URLSearchParams()
    params.append('ACMS_POST_Search_Items', true)
    params.append(this.keyName, value)
    params.append('formToken', window.csrfToken)

    this.source = CancelToken.source()

    return new Promise((resolve, reject) => {
      axiosLib({
        method: 'POST',
        url: endpoint,
        responseType: 'json',
        cancelToken: this.source.token,
        data: params,
      })
        .then((response) => {
          resolve(response.data)
        })
        .catch((thrown) => {
          if (!axiosLib.isCancel(thrown)) {
            reject(thrown.message)
          }
        })
    })
  }
}
