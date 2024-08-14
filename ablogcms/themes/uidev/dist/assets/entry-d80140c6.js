import { t as N, C as K, E as j } from './vendor-b4af7ed9.js'
import { e as V } from './index-2000a71d.js'
const G = () =>
    (
      Date.now().toString(36) + Math.random().toString(36).substr(2, 5)
    ).toUpperCase(),
  Z = (e, r) => {
    const n = G()
    e.setAttribute('id', n), document.styleSheets[0].insertRule(`#${n}{${r}}`)
  },
  ee = () => {
    N(() => {
      const r = document
        .querySelector('.js-auto-link-container')
        .querySelectorAll('h2, h3, h4')
      ;[].forEach.call(r, (n) => {
        const c = G(),
          t = document.createElement('a')
        ;(n.id = c),
          (t.href = `#${c}`),
          (t.className = 'c-header-anchor-link'),
          n.insertBefore(t, n.firstChild)
      })
    })
  },
  te = () => {
    N(() => {
      ;((r) => {
        const n = typeof r == 'string' ? document.querySelectorAll(r) : r,
          c = document.createElement('div')
        ;(c.className = 'cover'),
          Z(
            c,
            `
            position: fixed;
            z-index: -1;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            inset: 0;
            cursor: auto;
          `,
          )
        function d() {
          const { id: m } = this.dataset,
            { ariaExpanded: E } = this,
            g = document.querySelector(m)
          if (E === 'true')
            (g.style.display = 'none'),
              (this.ariaExpanded = 'false'),
              this.removeChild(this.querySelector('.cover'))
          else {
            if (![].every.call(n, ({ ariaExpanded: s }) => s === 'false'))
              return
            ;(g.style.display = 'block'),
              (this.ariaExpanded = 'true'),
              this.appendChild(c)
          }
        }
        ;[].forEach.call(n, (m) => {
          m.addEventListener('click', d)
        })
      })('.js-popup')
    })
  }
var z = { exports: {} },
  O = {}
Object.defineProperty(O, '__esModule', { value: !0 })
O.append = function (r, n) {
  var c = new DOMParser(),
    t = c.parseFromString(n, 'text/html')
  r.appendChild(t.querySelector('body').childNodes[0])
}
O.prepend = function (r, n) {
  var c = new DOMParser(),
    t = c.parseFromString(n, 'text/html')
  r.insertBefore(t.querySelector('body').childNodes[0], r.firstChild)
}
O.getUniqId = function () {
  return (
    Date.now().toString(36) + Math.random().toString(36).substr(2, 5)
  ).toUpperCase()
}
O.remove = function (r) {
  r && r.parentNode && r.parentNode.removeChild(r)
}
O.addClass = function (r, n) {
  r.classList ? r.classList.add(n) : (r.className += ' ' + n)
}
O.triggerEvent = function (r, n, c) {
  var t = void 0
  window.CustomEvent
    ? (t = new CustomEvent(n, { cancelable: !0 }))
    : ((t = document.createEvent('CustomEvent')),
      t.initCustomEvent(n, !1, !1, c)),
    r.dispatchEvent(t)
}
var re = (O.getScrollTop = function () {
    return (
      window.pageYOffset ||
      document.documentElement.scrollTop ||
      document.body.scrollTop ||
      0
    )
  }),
  ne = (O.getScrollLeft = function () {
    return (
      window.pageXOffset ||
      document.documentElement.scrollLeft ||
      document.body.scrollLeft ||
      0
    )
  })
O.getOffset = function (r) {
  var n = r.getBoundingClientRect()
  return { top: n.top + re(), left: n.left + ne() }
}
O.before = function (r, n) {
  r.insertAdjacentHTML('beforebegin', n)
}
O.outerHeight = function (r) {
  var n = r.offsetHeight,
    c = getComputedStyle(r)
  return (n += parseInt(c.marginTop) + parseInt(c.marginBottom)), n
}
O.selfHeight = function (r) {
  var n = r.offsetHeight,
    c = getComputedStyle(r)
  return (n -= parseInt(c.paddingTop) + parseInt(c.paddingBottom)), n
}
;(function (e, r) {
  Object.defineProperty(r, '__esModule', { value: !0 })
  var n = (function () {
      function g(o, s) {
        for (var l = 0; l < s.length; l++) {
          var f = s[l]
          ;(f.enumerable = f.enumerable || !1),
            (f.configurable = !0),
            'value' in f && (f.writable = !0),
            Object.defineProperty(o, f.key, f)
        }
      }
      return function (o, s, l) {
        return s && g(o.prototype, s), l && g(o, l), o
      }
    })(),
    c = O
  function t(g, o) {
    if (!(g instanceof o))
      throw new TypeError('Cannot call a class as a function')
  }
  var d = V.assign,
    m = {
      container: 'body',
      condition: function () {
        return !0
      },
      offsetTop: 0,
      offsetBottom: 0,
      breakpoint: 0,
    },
    E = (function () {
      function g(o, s) {
        var l = this
        t(this, g),
          (this.opt = d({}, m, s)),
          (this.scrollAmount = -this.opt.offsetTop),
          (this.scrollOld = 0),
          (this.containerElement =
            typeof this.opt.container == 'string'
              ? document.querySelector(this.opt.container)
              : this.opt.container),
          (this.targetElement =
            typeof o == 'string' ? document.querySelector(o) : o),
          (this.targetWidth = this.targetElement.style.width),
          (this.targetBoxSizing = this.targetElement.style.boxSizing),
          (0, c.before)(
            this.targetElement,
            '<div class="js-pretty-scroll-before"></div>',
          ),
          (this.beforeElement = this.targetElement.previousElementSibling),
          (this.parentElement = this.targetElement.parentElement),
          (this.parentElement.style.position = 'relative'),
          window.addEventListener('scroll', function () {
            l.onScroll()
          }),
          window.addEventListener('resize', function () {
            l.onScroll()
          })
      }
      return (
        n(g, [
          {
            key: 'applyStyle',
            value: function (s) {
              var l = this.targetElement
              for (var f in s) l.style[f] = s[f]
            },
          },
          {
            key: 'onScroll',
            value: function () {
              var s = (0, c.getScrollTop)(),
                l = this.beforeElement,
                f = this.containerElement,
                b = this.targetElement,
                p = this.targetWidth,
                k = this.targetBoxSizing,
                L = this.opt,
                a = L.offsetTop,
                i = L.offsetBottom,
                u = L.condition,
                h = L.breakpoint,
                v = window.innerHeight,
                S = window.innerWidth,
                x = (0, c.outerHeight)(b),
                C = (0, c.getOffset)(l).top,
                q = (0, c.outerHeight)(f),
                H = parseInt(getComputedStyle(f).paddingBottom, 10),
                T = (0, c.getOffset)(f).top,
                w = q + T,
                A = v > x ? x + a + i : v,
                _ = x - v,
                X = l.offsetTop,
                R = l.offsetLeft,
                J = C - T,
                y = { position: 'static', width: p, boxSizing: k }
              if (!u()) {
                ;(l.style.height = '0px'), this.applyStyle(y)
                return
              }
              if (h >= S) {
                ;(l.style.height = '0px'), this.applyStyle(y)
                return
              }
              if (s < C - a) {
                ;(l.style.height = '0px'),
                  this.applyStyle(y),
                  (this.scrollOld = s)
                return
              }
              ;(y.width = l.offsetWidth + 'px'),
                (y.boxSizing = 'border-box'),
                s + A + H <= w
                  ? ((this.scrollAmount += s - this.scrollOld),
                    (this.scrollOld = s),
                    this.scrollAmount > _ + i
                      ? (this.scrollAmount = _ + i)
                      : this.scrollAmount < -a && (this.scrollAmount = -a),
                    this.scrollAmount === _ + i || this.scrollAmount + a === 0
                      ? ((y.position = 'fixed'),
                        this.scrollAmount + a === 0 || x < v
                          ? (y.top = a + 'px')
                          : (y.top = v - x - i + 'px'),
                        (y.left = (0, c.getOffset)(l).left + 'px'))
                      : ((y.position = 'absolute'),
                        s - this.scrollAmount < C
                          ? (y.top = X + 'px')
                          : (y.top = s - this.scrollAmount - C + 'px'),
                        (y.left = R + 'px')))
                  : ((y.position = 'absolute'),
                    (y.top = q - x - J - H - i + 'px'),
                    (y.left = R + 'px')),
                (y.position === 'absolute' || y.position === 'fixed') &&
                  (l.style.height = x + 'px'),
                this.applyStyle(y)
            },
          },
        ]),
        g
      )
    })()
  ;(r.default = E), (e.exports = r.default)
})(z, z.exports)
const le = () => {
  N(() => {})
}
var Q = { exports: {} },
  I,
  U
function oe() {
  return (
    U ||
      ((U = 1),
      (I = {
        tocSelector: '.js-toc',
        contentSelector: '.js-toc-content',
        headingSelector: 'h1, h2, h3',
        ignoreSelector: '.js-toc-ignore',
        hasInnerContainers: !1,
        linkClass: 'toc-link',
        extraLinkClasses: '',
        activeLinkClass: 'is-active-link',
        listClass: 'toc-list',
        extraListClasses: '',
        isCollapsedClass: 'is-collapsed',
        collapsibleClass: 'is-collapsible',
        listItemClass: 'toc-list-item',
        activeListItemClass: 'is-active-li',
        collapseDepth: 0,
        scrollSmooth: !0,
        scrollSmoothDuration: 420,
        scrollSmoothOffset: 0,
        scrollEndCallback: function (e) {},
        headingsOffset: 1,
        throttleTimeout: 50,
        positionFixedSelector: null,
        positionFixedClass: 'is-position-fixed',
        fixedSidebarOffset: 'auto',
        includeHtml: !1,
        includeTitleTags: !1,
        onClick: function (e) {},
        orderedList: !0,
        scrollContainer: null,
        skipRendering: !1,
        headingLabelCallback: !1,
        ignoreHiddenElements: !1,
        headingObjectCallback: null,
        basePath: '',
        disableTocScrollSync: !1,
        tocScrollOffset: 0,
      })),
    I
  )
}
var P, M
function ie() {
  return (
    M ||
      ((M = 1),
      (P = function (e) {
        var r = [].forEach,
          n = [].some,
          c = document.body,
          t,
          d = !0,
          m = ' '
        function E(a, i) {
          var u = i.appendChild(o(a))
          if (a.children.length) {
            var h = s(a.isCollapsed)
            a.children.forEach(function (v) {
              E(v, h)
            }),
              u.appendChild(h)
          }
        }
        function g(a, i) {
          var u = !1,
            h = s(u)
          if (
            (i.forEach(function (v) {
              E(v, h)
            }),
            (t = a || t),
            t !== null)
          )
            return (
              t.firstChild && t.removeChild(t.firstChild),
              i.length === 0 ? t : t.appendChild(h)
            )
        }
        function o(a) {
          var i = document.createElement('li'),
            u = document.createElement('a')
          return (
            e.listItemClass && i.setAttribute('class', e.listItemClass),
            e.onClick && (u.onclick = e.onClick),
            e.includeTitleTags && u.setAttribute('title', a.textContent),
            e.includeHtml && a.childNodes.length
              ? r.call(a.childNodes, function (h) {
                  u.appendChild(h.cloneNode(!0))
                })
              : (u.textContent = a.textContent),
            u.setAttribute('href', e.basePath + '#' + a.id),
            u.setAttribute(
              'class',
              e.linkClass +
                m +
                'node-name--' +
                a.nodeName +
                m +
                e.extraLinkClasses,
            ),
            i.appendChild(u),
            i
          )
        }
        function s(a) {
          var i = e.orderedList ? 'ol' : 'ul',
            u = document.createElement(i),
            h = e.listClass + m + e.extraListClasses
          return (
            a &&
              ((h = h + m + e.collapsibleClass),
              (h = h + m + e.isCollapsedClass)),
            u.setAttribute('class', h),
            u
          )
        }
        function l() {
          if (e.scrollContainer && document.querySelector(e.scrollContainer)) {
            var a
            a = document.querySelector(e.scrollContainer).scrollTop
          } else a = document.documentElement.scrollTop || c.scrollTop
          var i = document.querySelector(e.positionFixedSelector)
          e.fixedSidebarOffset === 'auto' &&
            (e.fixedSidebarOffset = t.offsetTop),
            a > e.fixedSidebarOffset
              ? i.className.indexOf(e.positionFixedClass) === -1 &&
                (i.className += m + e.positionFixedClass)
              : (i.className = i.className
                  .split(m + e.positionFixedClass)
                  .join(''))
        }
        function f(a) {
          var i = 0
          return (
            a !== null &&
              ((i = a.offsetTop),
              e.hasInnerContainers && (i += f(a.offsetParent))),
            i
          )
        }
        function b(a) {
          if (e.scrollContainer && document.querySelector(e.scrollContainer)) {
            var i
            i = document.querySelector(e.scrollContainer).scrollTop
          } else i = document.documentElement.scrollTop || c.scrollTop
          e.positionFixedSelector && l()
          var u = a,
            h
          if (d && t !== null && u.length > 0) {
            n.call(u, function (T, w) {
              if (f(T) > i + e.headingsOffset + 10) {
                var A = w === 0 ? w : w - 1
                return (h = u[A]), !0
              } else if (w === u.length - 1) return (h = u[u.length - 1]), !0
            })
            var v = t.querySelector('.' + e.activeLinkClass),
              S = t.querySelector(
                '.' +
                  e.linkClass +
                  '.node-name--' +
                  h.nodeName +
                  '[href="' +
                  e.basePath +
                  '#' +
                  h.id.replace(/([ #;&,.+*~':"!^$[\]()=>|/\\@])/g, '\\$1') +
                  '"]',
              )
            if (v === S) return
            var x = t.querySelectorAll('.' + e.linkClass)
            r.call(x, function (T) {
              T.className = T.className.split(m + e.activeLinkClass).join('')
            })
            var C = t.querySelectorAll('.' + e.listItemClass)
            r.call(C, function (T) {
              T.className = T.className
                .split(m + e.activeListItemClass)
                .join('')
            }),
              S &&
                S.className.indexOf(e.activeLinkClass) === -1 &&
                (S.className += m + e.activeLinkClass)
            var q = S && S.parentNode
            q &&
              q.className.indexOf(e.activeListItemClass) === -1 &&
              (q.className += m + e.activeListItemClass)
            var H = t.querySelectorAll(
              '.' + e.listClass + '.' + e.collapsibleClass,
            )
            r.call(H, function (T) {
              T.className.indexOf(e.isCollapsedClass) === -1 &&
                (T.className += m + e.isCollapsedClass)
            }),
              S &&
                S.nextSibling &&
                S.nextSibling.className.indexOf(e.isCollapsedClass) !== -1 &&
                (S.nextSibling.className = S.nextSibling.className
                  .split(m + e.isCollapsedClass)
                  .join('')),
              p(S && S.parentNode.parentNode)
          }
        }
        function p(a) {
          return a &&
            a.className.indexOf(e.collapsibleClass) !== -1 &&
            a.className.indexOf(e.isCollapsedClass) !== -1
            ? ((a.className = a.className
                .split(m + e.isCollapsedClass)
                .join('')),
              p(a.parentNode.parentNode))
            : a
        }
        function k(a) {
          var i = a.target || a.srcElement
          typeof i.className != 'string' ||
            i.className.indexOf(e.linkClass) === -1 ||
            (d = !1)
        }
        function L() {
          d = !0
        }
        return {
          enableTocAnimation: L,
          disableTocAnimation: k,
          render: g,
          updateToc: b,
        }
      })),
    P
  )
}
var B, $
function se() {
  return (
    $ ||
      (($ = 1),
      (B = function (r) {
        var n = [].reduce
        function c(o) {
          return o[o.length - 1]
        }
        function t(o) {
          return +o.nodeName.toUpperCase().replace('H', '')
        }
        function d(o) {
          if (!(o instanceof window.HTMLElement)) return o
          if (r.ignoreHiddenElements && (!o.offsetHeight || !o.offsetParent))
            return null
          const s =
            o.getAttribute('data-heading-label') ||
            (r.headingLabelCallback
              ? String(r.headingLabelCallback(o.textContent))
              : o.textContent.trim())
          var l = {
            id: o.id,
            children: [],
            nodeName: o.nodeName,
            headingLevel: t(o),
            textContent: s,
          }
          return (
            r.includeHtml && (l.childNodes = o.childNodes),
            r.headingObjectCallback ? r.headingObjectCallback(l, o) : l
          )
        }
        function m(o, s) {
          for (
            var l = d(o),
              f = l.headingLevel,
              b = s,
              p = c(b),
              k = p ? p.headingLevel : 0,
              L = f - k;
            L > 0 && ((p = c(b)), !(p && f === p.headingLevel));

          )
            p && p.children !== void 0 && (b = p.children), L--
          return f >= r.collapseDepth && (l.isCollapsed = !0), b.push(l), b
        }
        function E(o, s) {
          var l = s
          r.ignoreSelector &&
            (l = s.split(',').map(function (b) {
              return b.trim() + ':not(' + r.ignoreSelector + ')'
            }))
          try {
            return o.querySelectorAll(l)
          } catch {
            return console.warn('Headers not found with selector: ' + l), null
          }
        }
        function g(o) {
          return n.call(
            o,
            function (l, f) {
              var b = d(f)
              return b && m(b, l.nest), l
            },
            { nest: [] },
          )
        }
        return { nestHeadingsArray: g, selectHeadings: E }
      })),
    B
  )
}
var D, W
function ae() {
  return (
    W ||
      ((W = 1),
      (D = function (r) {
        var n = r.tocElement || document.querySelector(r.tocSelector)
        if (n && n.scrollHeight > n.clientHeight) {
          var c = n.querySelector('.' + r.activeListItemClass)
          c && (n.scrollTop = c.offsetTop - r.tocScrollOffset)
        }
      })),
    D
  )
}
var F = {},
  Y
function ce() {
  if (Y) return F
  ;(Y = 1), (F.initSmoothScrolling = e)
  function e(n) {
    var c = n.duration,
      t = n.offset,
      d = location.hash ? g(location.href) : location.href
    m()
    function m() {
      document.body.addEventListener('click', s, !1)
      function s(l) {
        !E(l.target) ||
          l.target.className.indexOf('no-smooth-scroll') > -1 ||
          (l.target.href.charAt(l.target.href.length - 2) === '#' &&
            l.target.href.charAt(l.target.href.length - 1) === '!') ||
          l.target.className.indexOf(n.linkClass) === -1 ||
          r(l.target.hash, {
            duration: c,
            offset: t,
            callback: function () {
              o(l.target.hash)
            },
          })
      }
    }
    function E(s) {
      return (
        s.tagName.toLowerCase() === 'a' &&
        (s.hash.length > 0 || s.href.charAt(s.href.length - 1) === '#') &&
        (g(s.href) === d || g(s.href) + '#' === d)
      )
    }
    function g(s) {
      return s.slice(0, s.lastIndexOf('#'))
    }
    function o(s) {
      var l = document.getElementById(s.substring(1))
      l &&
        (/^(?:a|select|input|button|textarea)$/i.test(l.tagName) ||
          (l.tabIndex = -1),
        l.focus())
    }
  }
  function r(n, c) {
    var t = window.pageYOffset,
      d = {
        duration: c.duration,
        offset: c.offset || 0,
        callback: c.callback,
        easing: c.easing || b,
      },
      m =
        document.querySelector(
          '[id="' + decodeURI(n).split('#').join('') + '"]',
        ) || document.querySelector('[id="' + n.split('#').join('') + '"]'),
      E =
        typeof n == 'string'
          ? d.offset +
            (n
              ? (m && m.getBoundingClientRect().top) || 0
              : -(
                  document.documentElement.scrollTop || document.body.scrollTop
                ))
          : n,
      g = typeof d.duration == 'function' ? d.duration(E) : d.duration,
      o,
      s
    requestAnimationFrame(function (p) {
      ;(o = p), l(p)
    })
    function l(p) {
      ;(s = p - o),
        window.scrollTo(0, d.easing(s, t, E, g)),
        s < g ? requestAnimationFrame(l) : f()
    }
    function f() {
      window.scrollTo(0, t + E), typeof d.callback == 'function' && d.callback()
    }
    function b(p, k, L, a) {
      return (
        (p /= a / 2),
        p < 1 ? (L / 2) * p * p + k : (p--, (-L / 2) * (p * (p - 2) - 1) + k)
      )
    }
  }
  return F
}
;(function (e, r) {
  ;(function (n, c) {
    e.exports = c(n)
  })(typeof j < 'u' ? j : window || j, function (n) {
    var c = oe(),
      t = {},
      d = {},
      m = ie(),
      E = se(),
      g = ae(),
      o,
      s,
      l =
        !!n &&
        !!n.document &&
        !!n.document.querySelector &&
        !!n.addEventListener
    if (typeof window > 'u' && !l) return
    var f,
      b = Object.prototype.hasOwnProperty
    function p() {
      for (var i = {}, u = 0; u < arguments.length; u++) {
        var h = arguments[u]
        for (var v in h) b.call(h, v) && (i[v] = h[v])
      }
      return i
    }
    function k(i, u, h) {
      u || (u = 250)
      var v, S
      return function () {
        var x = h || this,
          C = +new Date(),
          q = arguments
        v && C < v + u
          ? (clearTimeout(S),
            (S = setTimeout(function () {
              ;(v = C), i.apply(x, q)
            }, u)))
          : ((v = C), i.apply(x, q))
      }
    }
    function L(i) {
      try {
        return i.contentElement || document.querySelector(i.contentSelector)
      } catch {
        return (
          console.warn('Contents element not found: ' + i.contentSelector), null
        )
      }
    }
    function a(i) {
      try {
        return i.tocElement || document.querySelector(i.tocSelector)
      } catch {
        return console.warn('TOC element not found: ' + i.tocSelector), null
      }
    }
    return (
      (d.destroy = function () {
        var i = a(t)
        i !== null &&
          (t.skipRendering || (i && (i.innerHTML = '')),
          t.scrollContainer && document.querySelector(t.scrollContainer)
            ? (document
                .querySelector(t.scrollContainer)
                .removeEventListener('scroll', this._scrollListener, !1),
              document
                .querySelector(t.scrollContainer)
                .removeEventListener('resize', this._scrollListener, !1),
              o &&
                document
                  .querySelector(t.scrollContainer)
                  .removeEventListener('click', this._clickListener, !1))
            : (document.removeEventListener('scroll', this._scrollListener, !1),
              document.removeEventListener('resize', this._scrollListener, !1),
              o &&
                document.removeEventListener('click', this._clickListener, !1)))
      }),
      (d.init = function (i) {
        if (l) {
          ;(t = p(c, i || {})),
            (this.options = t),
            (this.state = {}),
            t.scrollSmooth &&
              ((t.duration = t.scrollSmoothDuration),
              (t.offset = t.scrollSmoothOffset),
              (d.scrollSmooth = ce().initSmoothScrolling(t))),
            (o = m(t)),
            (s = E(t)),
            (this._buildHtml = o),
            (this._parseContent = s),
            (this._headingsArray = f),
            d.destroy()
          var u = L(t)
          if (u !== null) {
            var h = a(t)
            if (
              h !== null &&
              ((f = s.selectHeadings(u, t.headingSelector)), f !== null)
            ) {
              var v = s.nestHeadingsArray(f),
                S = v.nest
              t.skipRendering || o.render(h, S),
                (this._scrollListener = k(function (C) {
                  o.updateToc(f), !t.disableTocScrollSync && g(t)
                  var q =
                    C &&
                    C.target &&
                    C.target.scrollingElement &&
                    C.target.scrollingElement.scrollTop === 0
                  ;((C && (C.eventPhase === 0 || C.currentTarget === null)) ||
                    q) &&
                    (o.updateToc(f),
                    t.scrollEndCallback && t.scrollEndCallback(C))
                }, t.throttleTimeout)),
                this._scrollListener(),
                t.scrollContainer && document.querySelector(t.scrollContainer)
                  ? (document
                      .querySelector(t.scrollContainer)
                      .addEventListener('scroll', this._scrollListener, !1),
                    document
                      .querySelector(t.scrollContainer)
                      .addEventListener('resize', this._scrollListener, !1))
                  : (document.addEventListener(
                      'scroll',
                      this._scrollListener,
                      !1,
                    ),
                    document.addEventListener(
                      'resize',
                      this._scrollListener,
                      !1,
                    ))
              var x = null
              return (
                (this._clickListener = k(function (C) {
                  t.scrollSmooth && o.disableTocAnimation(C),
                    o.updateToc(f),
                    x && clearTimeout(x),
                    (x = setTimeout(function () {
                      o.enableTocAnimation()
                    }, t.scrollSmoothDuration))
                }, t.throttleTimeout)),
                t.scrollContainer && document.querySelector(t.scrollContainer)
                  ? document
                      .querySelector(t.scrollContainer)
                      .addEventListener('click', this._clickListener, !1)
                  : document.addEventListener('click', this._clickListener, !1),
                this
              )
            }
          }
        }
      }),
      (d.refresh = function (i) {
        d.destroy(), d.init(i || this.options)
      }),
      (n.tocbot = d),
      d
    )
  })
})(Q)
var ue = Q.exports
const fe = K(ue)
const de = () => {
    N(() => {
      fe.init({
        tocSelector: '.js-toc',
        contentSelector: '.js-toc-content',
        headingSelector: 'h2, h3, h4',
        hasInnerContainers: !0,
        linkClass: 'c-toc__link',
        listClass: 'c-toc__list',
        listItemClass: 'c-toc__item',
        collapseDepth: 6,
      })
    })
  },
  pe = () => {
    ee(), te(), le(), de()
  }
export { pe as default }
