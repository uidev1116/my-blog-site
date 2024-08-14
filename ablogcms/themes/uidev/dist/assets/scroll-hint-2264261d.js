import { C as y } from './vendor-b4af7ed9.js'
import { e as H } from './index-2000a71d.js'
var h = { exports: {} },
  u = {}
Object.defineProperty(u, '__esModule', { value: !0 })
u.append = function (s, r) {
  var p = document.createElement('div')
  for (p.innerHTML = r; p.children.length > 0; ) s.appendChild(p.children[0])
}
u.addClass = function (s, r) {
  s.classList ? s.classList.add(r) : (s.className += ' ' + r)
}
u.removeClass = function (s, r) {
  s.classList
    ? s.classList.remove(r)
    : (s.className = s.className.replace(
        new RegExp('(^|\\b)' + r.split(' ').join('|') + '(\\b|$)', 'gi'),
        ' ',
      ))
}
var I = (u.getScrollTop = function () {
    return (
      window.pageYOffset ||
      document.documentElement.scrollTop ||
      document.body.scrollTop ||
      0
    )
  }),
  T = (u.getScrollLeft = function () {
    return (
      window.pageXOffset ||
      document.documentElement.scrollLeft ||
      document.body.scrollLeft ||
      0
    )
  })
u.getOffset = function (s) {
  var r = s.getBoundingClientRect()
  return { top: r.top + I(), left: r.left + T() }
}
;(function (f, s) {
  Object.defineProperty(s, '__esModule', { value: !0 })
  var r = (function () {
      function d(a, e) {
        for (var l = 0; l < e.length; l++) {
          var t = e[l]
          ;(t.enumerable = t.enumerable || !1),
            (t.configurable = !0),
            'value' in t && (t.writable = !0),
            Object.defineProperty(a, t.key, t)
        }
      }
      return function (a, e, l) {
        return e && d(a.prototype, e), l && d(a, l), a
      }
    })(),
    p = H,
    n = u
  function S(d, a) {
    if (!(d instanceof a))
      throw new TypeError('Cannot call a class as a function')
  }
  var m = {
      suggestClass: 'is-active',
      scrollableClass: 'is-scrollable',
      scrollableRightClass: 'is-right-scrollable',
      scrollableLeftClass: 'is-left-scrollable',
      scrollHintClass: 'scroll-hint',
      scrollHintIconClass: 'scroll-hint-icon',
      scrollHintIconAppendClass: '',
      scrollHintIconWrapClass: 'scroll-hint-icon-wrap',
      scrollHintText: 'scroll-hint-text',
      scrollHintBorderWidth: 10,
      remainingTime: -1,
      enableOverflowScrolling: !0,
      applyToParents: !1,
      suggestiveShadow: !1,
      offset: 0,
      i18n: { scrollable: 'scrollable' },
    },
    w = (function () {
      function d(a, e) {
        var l = this
        S(this, d), (this.opt = (0, p.assign)({}, m, e)), (this.items = [])
        var t = typeof a == 'string' ? document.querySelectorAll(a) : a,
          i = this.opt.applyToParents
        ;[].forEach.call(t, function (o) {
          i && (o = o.parentElement),
            (o.style.position = 'relative'),
            (o.style.overflow = 'auto'),
            l.opt.enableOverflowScrolling &&
              ('overflowScrolling' in o.style
                ? (o.style.overflowScrolling = 'touch')
                : 'webkitOverflowScrolling' in o.style &&
                  (o.style.webkitOverflowScrolling = 'touch'))
          var c = { element: o, scrolledIn: !1, interacted: !1 }
          document.addEventListener(
            'scroll',
            function (v) {
              v.target === o && ((c.interacted = !0), l.updateItem(c))
            },
            !0,
          ),
            (0, n.addClass)(o, l.opt.scrollHintClass),
            (0, n.append)(
              o,
              '<div class="' +
                l.opt.scrollHintIconWrapClass +
                `" data-target="scrollable-icon">
        <span class="` +
                l.opt.scrollHintIconClass +
                (l.opt.scrollHintIconAppendClass
                  ? ' ' + l.opt.scrollHintIconAppendClass
                  : '') +
                `">
          <div class="` +
                l.opt.scrollHintText +
                '">' +
                l.opt.i18n.scrollable +
                `</div>
        </span>
      </div>`,
            ),
            l.items.push(c)
        }),
          window.addEventListener('scroll', function () {
            l.updateItems()
          }),
          window.addEventListener('resize', function () {
            l.updateItems()
          }),
          this.updateItems()
      }
      return (
        r(d, [
          {
            key: 'isScrollable',
            value: function (e) {
              var l = this.opt.offset,
                t = e.element,
                i = t.offsetWidth
              return i + l < t.scrollWidth
            },
          },
          {
            key: 'checkScrollableDir',
            value: function (e) {
              var l = this.opt,
                t = l.scrollHintBorderWidth,
                i = l.scrollableRightClass,
                o = l.scrollableLeftClass,
                c = e.element,
                v = c.children[0],
                g = v.scrollWidth,
                b = c.offsetWidth,
                C = c.scrollLeft
              b + C < g - t ? (0, n.addClass)(c, i) : (0, n.removeClass)(c, i),
                b < g && C > t
                  ? (0, n.addClass)(c, o)
                  : (0, n.removeClass)(c, o)
            },
          },
          {
            key: 'needSuggest',
            value: function (e) {
              var l = e.scrolledIn,
                t = e.interacted
              return !t && l && this.isScrollable(e)
            },
          },
          {
            key: 'updateItems',
            value: function () {
              var e = this
              ;[].forEach.call(this.items, function (l) {
                e.updateItem(l)
              })
            },
          },
          {
            key: 'updateStatus',
            value: function (e) {
              var l = this,
                t = e.element,
                i = e.scrolledIn
              if (!i) {
                var o = t.querySelector(
                  '[data-target="scrollable-icon"] > span',
                )
                ;(0, n.getOffset)(o).top <
                  (0, n.getScrollTop)() + window.innerHeight &&
                  ((e.scrolledIn = !0),
                  this.opt.remainingTime !== -1 &&
                    setTimeout(function () {
                      ;(e.interacted = !0), l.updateItem(e)
                    }, this.opt.remainingTime))
              }
            },
          },
          {
            key: 'updateItem',
            value: function (e) {
              var l = this.opt,
                t = e.element,
                i = t.querySelector('[data-target="scrollable-icon"]')
              this.updateStatus(e),
                this.isScrollable(e)
                  ? (0, n.addClass)(t, l.scrollableClass)
                  : (0, n.removeClass)(t, l.scrollableClass),
                this.needSuggest(e)
                  ? (0, n.addClass)(i, l.suggestClass)
                  : (0, n.removeClass)(i, l.suggestClass),
                l.suggestiveShadow && this.checkScrollableDir(e)
            },
          },
        ]),
        d
      )
    })()
  ;(s.default = w), (f.exports = s.default)
})(h, h.exports)
var L = h.exports
const O = y(L)
const W = (f, s = {}) => {
  const r = {
    suggestClass: 'is-active',
    scrollableClass: 'is-scrollable',
    scrollableRightClass: 'is-right-scrollable',
    scrollableLeftClass: 'is-left-scrollable',
    scrollHintClass: 'scroll-hint',
    scrollHintIconClass: 'scroll-hint-icon',
    scrollHintIconAppendClass: 'scroll-hint-icon-white',
    scrollHintIconWrapClass: 'scroll-hint-icon-wrap',
    scrollHintText: 'scroll-hint-text',
    remainingTime: -1,
    scrollHintBorderWidth: 10,
    enableOverflowScrolling: !0,
    suggestiveShadow: !1,
    applyToParents: !1,
    i18n: { scrollable: 'スクロールできます' },
  }
  new O(f, Object.assign(r, s))
}
export { W as default }
