var b =
  typeof globalThis < 'u'
    ? globalThis
    : typeof window < 'u'
    ? window
    : typeof global < 'u'
    ? global
    : typeof self < 'u'
    ? self
    : {}
function v(e) {
  return e && e.__esModule && Object.prototype.hasOwnProperty.call(e, 'default')
    ? e.default
    : e
}
function w(e) {
  if (e.__esModule) return e
  var o = e.default
  if (typeof o == 'function') {
    var n = function s() {
      return this instanceof s
        ? Reflect.construct(o, arguments, this.constructor)
        : o.apply(this, arguments)
    }
    n.prototype = o.prototype
  } else n = {}
  return (
    Object.defineProperty(n, '__esModule', { value: !0 }),
    Object.keys(e).forEach(function (s) {
      var i = Object.getOwnPropertyDescriptor(e, s)
      Object.defineProperty(
        n,
        s,
        i.get
          ? i
          : {
              enumerable: !0,
              get: function () {
                return e[s]
              },
            },
      )
    }),
    n
  )
}
const m = 'modulepreload',
  y = function (e, o) {
    return new URL(e, o).href
  },
  h = {},
  E = function (o, n, s) {
    let i = Promise.resolve()
    if (n && n.length > 0) {
      const c = document.getElementsByTagName('link'),
        t = document.querySelector('meta[property=csp-nonce]'),
        d =
          (t == null ? void 0 : t.nonce) ||
          (t == null ? void 0 : t.getAttribute('nonce'))
      i = Promise.all(
        n.map((r) => {
          if (((r = y(r, s)), r in h)) return
          h[r] = !0
          const u = r.endsWith('.css'),
            p = u ? '[rel="stylesheet"]' : ''
          if (!!s)
            for (let a = c.length - 1; a >= 0; a--) {
              const f = c[a]
              if (f.href === r && (!u || f.rel === 'stylesheet')) return
            }
          else if (document.querySelector(`link[href="${r}"]${p}`)) return
          const l = document.createElement('link')
          if (
            ((l.rel = u ? 'stylesheet' : m),
            u || ((l.as = 'script'), (l.crossOrigin = '')),
            (l.href = r),
            d && l.setAttribute('nonce', d),
            document.head.appendChild(l),
            u)
          )
            return new Promise((a, f) => {
              l.addEventListener('load', a),
                l.addEventListener('error', () =>
                  f(new Error(`Unable to preload CSS for ${r}`)),
                )
            })
        }),
      )
    }
    return i
      .then(() => o())
      .catch((c) => {
        const t = new Event('vite:preloadError', { cancelable: !0 })
        if (((t.payload = c), window.dispatchEvent(t), !t.defaultPrevented))
          throw c
      })
  }
export { E as _, w as a, b as c, v as g }
