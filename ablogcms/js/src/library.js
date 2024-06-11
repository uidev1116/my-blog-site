import punycode from 'punycode'
import dayjs from 'dayjs'
import lozad from 'lozad'
import { Loader } from 'google-maps'
import PrettyScroll from 'pretty-scroll'
import ResizeImage from './lib/resize-image/resize-image'
import { setDropArea } from './lib/utility'
import { getParameterByName, PerfectFormData, encodeUri } from './utils'

export default () => {
  ACMS.Library.PerfectFormData = PerfectFormData

  ACMS.Library.PrettyScroll = PrettyScroll

  ACMS.Library.lozad = lozad

  ACMS.Library.LazyLoad = (selector, config) => {
    const observer = lozad(selector, config)
    observer.observe()
  }

  ACMS.Library.ResizeImage = (elm) => {
    const resizeImage = new ResizeImage(elm)
    resizeImage.resize()
  }

  ACMS.Library.geolocation = (successCallable, errorCallable) => {
    if (!navigator.geolocation) {
      errorCallable(ACMS.i18n('geo_location.not_supported'))
      return
    }
    window.navigator.geolocation.getCurrentPosition(
      (position) => {
        successCallable(position.coords.latitude, position.coords.longitude)
      },
      (error) => {
        const errorMessage = {
          0: ACMS.i18n('geo_location.unknown_error'),
          1: ACMS.i18n('geo_location.user_denied'),
          2: ACMS.i18n('geo_location.information_error'),
          3: ACMS.i18n('geo_location.timed_out'),
        }
        errorCallable(errorMessage[error.code])
      },
      {
        enableHighAccuracy: true,
        timeout: 30000,
        maximumAge: 10000,
      },
    )
  }

  ACMS.Library.Dayjs = (input, format) => dayjs(input).format(format)

  ACMS.Library.SmartPhoto = (context) => {
    import(
      /* webpackChunkName: "smartphoto-css" */ 'smartphoto/css/smartphoto.min.css'
    )
    import(/* webpackChunkName: "smartphoto" */ 'smartphoto').then(
      ({ default: SmartPhoto }) => {
        new SmartPhoto(context, ACMS.Config.SmartPhotoConfig) // eslint-disable-line no-new
      },
    )
  }

  //-------------
  // modalVideo
  ACMS.Library.modalVideo = (context) => {
    import(
      /* webpackChunkName: "modal-video-css" */ 'modal-video/css/modal-video.min.css'
    )
    import(/* webpackChunkName: "modal-video" */ 'modal-video').then(
      ({ default: ModalVideo }) => {
        new ModalVideo(context, ACMS.Config.modalVideoConfig) // eslint-disable-line no-new
      },
    )
  }

  ACMS.Library.decodeEntities = (text) => {
    const entitiesArray = [
      ['amp', '&'],
      ['apos', "'"],
      ['#x27', "'"],
      ['#x2F', '/'],
      ['#39', "'"],
      ['#47', '/'],
      ['lt', '<'],
      ['gt', '>'],
      ['nbsp', ' '],
      ['quot', '"'],
    ]

    for (let i = 0, max = entitiesArray.length; i < max; i += 1) {
      text = text.replace(
        new RegExp(`&${entitiesArray[i][0]};`, 'g'),
        entitiesArray[i][1],
      )
    }

    return text
  }

  //------------------
  // punycode encode
  ACMS.Library.punycodeEncode = function (domain) {
    if (typeof domain === 'object' && domain.baseVal) {
      domain = $('<a>').attr('href', domain.baseVal).get(0).href
    }
    let punycodeString = ''
    let tmp = ''
    let isMultiByte = false
    let matched = false
    if (typeof domain !== 'string') {
      return punycodeString
    }
    matched = domain.match(/^[httpsfile]+:\/{2,3}([^\/]+)/i) // eslint-disable-line no-useless-escape
    if (matched) {
      domain = matched[1] // eslint-disable-line prefer-destructuring
    }

    for (let i = 0; i < domain.length; i++) {
      const code = domain.charCodeAt(i)
      if (code >= 256) {
        isMultiByte = true
        tmp += String.fromCharCode(code)
      } else {
        if (tmp.length > 0) {
          punycodeString += punycode.encode(tmp)
          tmp = ''
        }
        punycodeString += String.fromCharCode(code)
      }
    }

    if (isMultiByte) {
      punycodeString = `xn--${punycodeString}`
    }

    return punycodeString
  }

  //---------
  // locales
  ACMS.Library.lang = function (list, fallback) {
    let lang =
      (window.navigator.languages && window.navigator.languages[0]) ||
      window.navigator.language ||
      window.navigator.userLanguage ||
      window.navigator.browserLanguage

    lang = lang.replace(/-(.*)$/g, '')

    list = list || ['ja', 'en']
    fallback = fallback || 'ja'

    if (_.indexOf(list, lang) === -1) {
      lang = fallback
    }

    return lang
  }

  //----------
  // scrollTo
  ACMS.Library.scrollTo = function (x, y, m, k, offset, callback) {
    y += offset
    callback = callback || function () {}

    const left = Math.floor(
      document.body.scrollLeft || document.documentElement.scrollLeft,
    )
    const top = Math.floor(
      document.body.scrollTop || document.documentElement.scrollTop,
    )
    let remainX = x - left
    let remainY = y - top

    const calc = function () {
      const h = parseInt(x - remainX, 10)
      const v = parseInt(y - remainY, 10)
      remainX *= 1 - k
      remainY *= 1 - k
      if (parseInt(remainX, 10) !== 0 || parseInt(remainY, 10) !== 0) {
        window.scrollTo(h, v)
        setTimeout(calc, m)
      } else {
        window.scrollTo(x, y)
        callback()
      }
    }
    setTimeout(calc, m)
  }

  //-------------
  // scrollToElm
  ACMS.Library.scrollToElm = function (elm, setting) {
    let xy
    if (elm && $(elm).size()) {
      xy = $(elm).offset()
    } else {
      xy = { left: 0, top: 0 }
    }

    setting = $.extend(
      {
        x: xy.left,
        y: xy.top,
        m: ACMS.Config.scrollToI,
        k: ACMS.Config.scrollToV,
        offset: 0,
        callback: null,
      },
      setting,
    )

    ACMS.Library.scrollTo(
      setting.x,
      setting.y,
      setting.m,
      setting.k,
      setting.offset,
      setting.callback,
    )
  }

  //------------
  // dl2object
  ACMS.Library.dl2object = function (dl) {
    const ret = {}
    $('dt', dl).each(function () {
      const $dt = $(this)
      const $dd = $dt.next()
      if ($dt.text() === '') {
        return false
      }
      if ($dd[0].tagName.toUpperCase() !== 'DD') {
        return false
      }
      ret[$.trim($dt.text().replace('&', '%26'))] = $.trim(
        $dd.text().replace('&', '%26'),
      )
    })

    return ret
  }

  //-------------
  // switchStyle
  ACMS.Library.switchStyle = function (styleName, $link) {
    $link.each(function () {
      this.disabled = true
      if (styleName === this.title) {
        this.disabled = false
        $.cookie('styleName', styleName, { path: '/' })
      }
    })
  }

  //-----------------
  // googleLoadProxy
  ACMS.Library.googleLoadProcessing = false
  ACMS.Library.googleLoadCompleted = false
  ACMS.Library.googleLoadProxy = function (api, ver, params) {
    const { callback } = params
    const options = params.options || {}
    const _load = () => {
      const loader = new Loader(ACMS.Config.googleApiKey, options)
      loader.load().then((google) => {
        window.google = google
        ACMS.Library.googleLoadProcessing = false
        ACMS.Library.googleLoadCompleted = true
        if ($.isFunction(callback)) {
          callback()
        }
      })
    }
    // apiが既に読み込まれていれば即時実行
    if (ACMS.Library.googleLoadCompleted) {
      if ($.isFunction(callback)) {
        callback()
      }
      return
    }
    if (ACMS.Library.googleLoadProcessing) {
      const timer = setInterval(() => {
        if (!ACMS.Library.googleLoadProcessing) {
          clearInterval(timer)
          ACMS.Library.googleLoadProxy(api, ver, params)
        }
      }, 50)
    } else {
      ACMS.Library.googleLoadProcessing = true
      _load()
    }
  }

  //-------------
  // getPostData
  ACMS.Library.getPostData = function (context) {
    const data = {}
    const cnt = {}

    $(
      ':input:not(disabled):not(:radio:not(:checked)):not(:checkbox:not(:checked))',
      context,
    ).each(function () {
      const name = this.name.replace(/\[\]$/, '')
      const isAry = name !== this.name
      const val = $(this).val()

      if (isAry && typeof cnt[name] === 'undefined') {
        cnt[name] = 0
      }
      if (typeof val === 'string') {
        if (isAry) {
          data[`${name}[${cnt[name]++}]`] = val
        } else {
          data[name] = val
        }
      } else {
        for (const i in val) {
          data[`${name}[${cnt[name]++}]`] = val[i]
        }
      }
    })

    return data
  }

  //--------------------
  // getParameterByName
  ACMS.Library.getParameterByName = getParameterByName

  //----------------------
  // google code prettify
  ACMS.Library.googleCodePrettifyPost = () => {
    $('pre').addClass(ACMS.Config.googleCodePrettifyClass)
    if (!$('pre').hasClass('acms-admin-customfield-maker')) {
      if (typeof prettyPrint === 'function') {
        prettyPrint()
      } else {
        ACMS.Library.googleCodePrettify()
      }
    }
  }

  //----------
  // acmsLink
  ACMS.Library.acmsLink = function (Uri, inherit) {
    const { Config } = ACMS
    function empty(value) {
      return typeof value === 'undefined' || value === null
    }

    //-----------------
    // inherit context
    if (inherit) {
      if (empty(Uri.cid)) {
        Uri.cid = Config.cid
      }
      if (empty(Uri.eid)) {
        Uri.eid = Config.eid
      }
      if (empty(Uri.admin)) {
        Uri.admin = Config.admin
      }
      if (empty(Uri.keyword)) {
        Uri.keyword = Config.keyword
      }
    }

    let url = Config.scriptRoot
    url += Uri.bid
      ? `bid/${encodeUri(Uri.bid)}`
      : `bid/${encodeUri(Config.bid)}`
    if (Uri.date) {
      url += `/${Uri.date}`
    }
    if (Uri.cid) {
      url += `/cid/${encodeUri(Uri.cid)}`
    }
    if (Uri.eid) {
      url += `/eid/${encodeUri(Uri.eid)}`
    }
    if (Uri.utid) {
      url += `/utid/${encodeUri(Uri.utid)}`
    }
    if (Uri.admin) {
      url += `/admin/${encodeUri(Uri.admin)}`
    }
    if (Uri.order) {
      url += `/order/${encodeUri(Uri.order)}`
    }
    if (Uri.keyword) {
      url += `/keyword/${encodeUri(Uri.keyword)}`
    }
    if (Uri.page) {
      url += `/page/${encodeUri(Uri.page)}`
    }
    if (Uri.limit) {
      url += `/limit/${encodeUri(Uri.limit)}`
    }
    if (Uri.tag) {
      url += `/tag/${Uri.tag.split('/').map(encodeUri).join('/')}`
    }
    if (Uri.tpl) {
      url += `/tpl/${Uri.tpl.split('/').map(encodeUri).join('/')}`
    }
    if (Uri.tpl && /^ajax\//.test(Uri.tpl)) {
      if (Uri.Query) {
        Uri.Query.v = Date.now()
      } else {
        Uri.Query = { v: Date.now() }
      }
    }
    url += '/'
    if (Uri.Query) {
      const query = []
      $.each(Uri.Query, function (key) {
        let pair = ''
        pair += encodeUri(key)
        if (this !== true) {
          pair += `=${encodeUri(this)}`
        }
        query.push(pair)
      })
      if (query.length) {
        url += `?${query.join('&')}`
      }
    }

    return url
  }

  ACMS.Library.exFeature = function () {
    return (
      ACMS.Config.experimentalFeature === true || $.cookie('acms_ex') === 'on'
    )
  }

  ACMS.Library.queryToObj = function (str) {
    str = str || location.search
    const result = {}
    const param = str.substring(str.indexOf('?') + 1).split('&')
    let hash

    for (let i = 0; i < param.length; i++) {
      hash = param[i].split('=')
      result[hash[0]] = hash[1] // eslint-disable-line prefer-destructuring
    }
    return result
  }

  /**
   *
   * @param {String} name
   * @param {Object} [options]
   */
  ACMS.Library.toggleNotify = function (name, options) {
    if (options) {
      options = {}
    }

    const ident = `js-notify-${name}`
    const message = options.message || ''
    const preCallback = options.preCallback || false
    const postCallback = options.postCallback || false
    const style = options.style || false
    let $notify = $(`#${ident}`)

    if (!$notify.length) {
      $notify = $(
        $.parseHTML(`<div id="${ident}" class="js-notify">${message}</div>`),
      )
      $notify.appendTo('body')
    }

    // スタイルの上書き
    if (style) {
      $notify.css(style)
    }

    if ($notify.css('display') === 'none') {
      if (preCallback) {
        preCallback($notify)
      }
      $notify.fadeIn(300, () => {
        if (postCallback) {
          postCallback($notify)
        }
      })
    } else {
      if (preCallback) {
        preCallback($notify)
      }
      $notify.fadeOut(200, () => {
        $notify.hide()
        if (postCallback) {
          postCallback($notify)
        }
      })
    }
  }

  ACMS.Library.triggerEvent = (el, eventName, options) => {
    let event
    if (window.CustomEvent) {
      event = new CustomEvent(eventName, { cancelable: true })
    } else {
      event = document.createEvent('CustomEvent')
      event.initCustomEvent(eventName, false, false, options)
    }
    el.dispatchEvent(event)
  }

  ACMS.Library.setDropArea = setDropArea
}
