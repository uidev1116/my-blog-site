import { a as mt, c as at, g as gt } from './preload-helper-CqCcXmTM.js'
var it = { exports: {} },
  ot = { exports: {} }
Array.prototype.find ||
  Object.defineProperty(Array.prototype, 'find', {
    value: function (u) {
      if (this == null) throw new TypeError('this is null or not defined')
      var o = Object(this),
        n = o.length >>> 0
      if (typeof u != 'function')
        throw new TypeError('predicate must be a function')
      for (var l = arguments[1], p = 0; p < n; ) {
        var b = o[p]
        if (u.call(l, b, p, o)) return b
        p++
      }
    },
  })
var st = 11
function yt(u, o) {
  var n = o.attributes,
    l,
    p,
    b,
    N,
    D
  if (!(o.nodeType === st || u.nodeType === st)) {
    for (var T = n.length - 1; T >= 0; T--)
      (l = n[T]),
        (p = l.name),
        (b = l.namespaceURI),
        (N = l.value),
        b
          ? ((p = l.localName || p),
            (D = u.getAttributeNS(b, p)),
            D !== N &&
              (l.prefix === 'xmlns' && (p = l.name), u.setAttributeNS(b, p, N)))
          : ((D = u.getAttribute(p)), D !== N && u.setAttribute(p, N))
    for (var z = u.attributes, C = z.length - 1; C >= 0; C--)
      (l = z[C]),
        (p = l.name),
        (b = l.namespaceURI),
        b
          ? ((p = l.localName || p),
            o.hasAttributeNS(b, p) || u.removeAttributeNS(b, p))
          : o.hasAttribute(p) || u.removeAttribute(p)
  }
}
var K,
  wt = 'http://www.w3.org/1999/xhtml',
  M = typeof document > 'u' ? void 0 : document,
  Pt = !!M && 'content' in M.createElement('template'),
  _t = !!M && M.createRange && 'createContextualFragment' in M.createRange()
function Et(u) {
  var o = M.createElement('template')
  return (o.innerHTML = u), o.content.childNodes[0]
}
function xt(u) {
  K || ((K = M.createRange()), K.selectNode(M.body))
  var o = K.createContextualFragment(u)
  return o.childNodes[0]
}
function bt(u) {
  var o = M.createElement('body')
  return (o.innerHTML = u), o.childNodes[0]
}
function It(u) {
  return (u = u.trim()), Pt ? Et(u) : _t ? xt(u) : bt(u)
}
function tt(u, o) {
  var n = u.nodeName,
    l = o.nodeName,
    p,
    b
  return n === l
    ? !0
    : ((p = n.charCodeAt(0)),
      (b = l.charCodeAt(0)),
      p <= 90 && b >= 97
        ? n === l.toUpperCase()
        : b <= 90 && p >= 97
        ? l === n.toUpperCase()
        : !1)
}
function St(u, o) {
  return !o || o === wt ? M.createElement(u) : M.createElementNS(o, u)
}
function Nt(u, o) {
  for (var n = u.firstChild; n; ) {
    var l = n.nextSibling
    o.appendChild(n), (n = l)
  }
  return o
}
function rt(u, o, n) {
  u[n] !== o[n] &&
    ((u[n] = o[n]), u[n] ? u.setAttribute(n, '') : u.removeAttribute(n))
}
var ht = {
    OPTION: function (u, o) {
      var n = u.parentNode
      if (n) {
        var l = n.nodeName.toUpperCase()
        l === 'OPTGROUP' &&
          ((n = n.parentNode), (l = n && n.nodeName.toUpperCase())),
          l === 'SELECT' &&
            !n.hasAttribute('multiple') &&
            (u.hasAttribute('selected') &&
              !o.selected &&
              (u.setAttribute('selected', 'selected'),
              u.removeAttribute('selected')),
            (n.selectedIndex = -1))
      }
      rt(u, o, 'selected')
    },
    INPUT: function (u, o) {
      rt(u, o, 'checked'),
        rt(u, o, 'disabled'),
        u.value !== o.value && (u.value = o.value),
        o.hasAttribute('value') || u.removeAttribute('value')
    },
    TEXTAREA: function (u, o) {
      var n = o.value
      u.value !== n && (u.value = n)
      var l = u.firstChild
      if (l) {
        var p = l.nodeValue
        if (p == n || (!n && p == u.placeholder)) return
        l.nodeValue = n
      }
    },
    SELECT: function (u, o) {
      if (!o.hasAttribute('multiple')) {
        for (var n = -1, l = 0, p = u.firstChild, b, N; p; )
          if (((N = p.nodeName && p.nodeName.toUpperCase()), N === 'OPTGROUP'))
            (b = p), (p = b.firstChild)
          else {
            if (N === 'OPTION') {
              if (p.hasAttribute('selected')) {
                n = l
                break
              }
              l++
            }
            ;(p = p.nextSibling), !p && b && ((p = b.nextSibling), (b = null))
          }
        u.selectedIndex = n
      }
    },
  },
  J = 1,
  At = 11,
  dt = 3,
  lt = 8
function $() {}
function Dt(u) {
  if (u) return (u.getAttribute && u.getAttribute('id')) || u.id
}
function Bt(u) {
  return function (n, l, p) {
    if ((p || (p = {}), typeof l == 'string'))
      if (
        n.nodeName === '#document' ||
        n.nodeName === 'HTML' ||
        n.nodeName === 'BODY'
      ) {
        var b = l
        ;(l = M.createElement('html')), (l.innerHTML = b)
      } else l = It(l)
    var N = p.getNodeKey || Dt,
      D = p.onBeforeNodeAdded || $,
      T = p.onNodeAdded || $,
      z = p.onBeforeElUpdated || $,
      C = p.onElUpdated || $,
      H = p.onBeforeNodeDiscarded || $,
      X = p.onNodeDiscarded || $,
      A = p.onBeforeElChildrenUpdated || $,
      y = p.childrenOnly === !0,
      s = Object.create(null),
      d = []
    function m(x) {
      d.push(x)
    }
    function f(x, w) {
      if (x.nodeType === J)
        for (var P = x.firstChild; P; ) {
          var S = void 0
          w && (S = N(P)) ? m(S) : (X(P), P.firstChild && f(P, w)),
            (P = P.nextSibling)
        }
    }
    function g(x, w, P) {
      H(x) !== !1 && (w && w.removeChild(x), X(x), f(x, P))
    }
    function _(x) {
      if (x.nodeType === J || x.nodeType === At)
        for (var w = x.firstChild; w; ) {
          var P = N(w)
          P && (s[P] = w), _(w), (w = w.nextSibling)
        }
    }
    _(n)
    function h(x) {
      T(x)
      for (var w = x.firstChild; w; ) {
        var P = w.nextSibling,
          S = N(w)
        if (S) {
          var O = s[S]
          O && tt(w, O) ? (w.parentNode.replaceChild(O, w), t(O, w)) : h(w)
        } else h(w)
        w = P
      }
    }
    function e(x, w, P) {
      for (; w; ) {
        var S = w.nextSibling
        ;(P = N(w)) ? m(P) : g(w, x, !0), (w = S)
      }
    }
    function t(x, w, P) {
      var S = N(w)
      S && delete s[S],
        !(!P && (z(x, w) === !1 || (u(x, w), C(x), A(x, w) === !1))) &&
          (x.nodeName !== 'TEXTAREA' ? r(x, w) : ht.TEXTAREA(x, w))
    }
    function r(x, w) {
      var P = w.firstChild,
        S = x.firstChild,
        O,
        G,
        U,
        q,
        Y
      t: for (; P; ) {
        for (q = P.nextSibling, O = N(P); S; ) {
          if (((U = S.nextSibling), P.isSameNode && P.isSameNode(S))) {
            ;(P = q), (S = U)
            continue t
          }
          G = N(S)
          var R = S.nodeType,
            W = void 0
          if (
            (R === P.nodeType &&
              (R === J
                ? (O
                    ? O !== G &&
                      ((Y = s[O])
                        ? U === Y
                          ? (W = !1)
                          : (x.insertBefore(Y, S),
                            G ? m(G) : g(S, x, !0),
                            (S = Y))
                        : (W = !1))
                    : G && (W = !1),
                  (W = W !== !1 && tt(S, P)),
                  W && t(S, P))
                : (R === dt || R == lt) &&
                  ((W = !0),
                  S.nodeValue !== P.nodeValue && (S.nodeValue = P.nodeValue))),
            W)
          ) {
            ;(P = q), (S = U)
            continue t
          }
          G ? m(G) : g(S, x, !0), (S = U)
        }
        if (O && (Y = s[O]) && tt(Y, P)) x.appendChild(Y), t(Y, P)
        else {
          var Z = D(P)
          Z !== !1 &&
            (Z && (P = Z),
            P.actualize && (P = P.actualize(x.ownerDocument || M)),
            x.appendChild(P),
            h(P))
        }
        ;(P = q), (S = U)
      }
      e(x, S, G)
      var V = ht[x.nodeName]
      V && V(x, w)
    }
    var a = n,
      i = a.nodeType,
      c = l.nodeType
    if (!y) {
      if (i === J)
        c === J
          ? tt(n, l) || (X(n), (a = Nt(n, St(l.nodeName, l.namespaceURI))))
          : (a = l)
      else if (i === dt || i === lt) {
        if (c === i)
          return a.nodeValue !== l.nodeValue && (a.nodeValue = l.nodeValue), a
        a = l
      }
    }
    if (a === l) X(n)
    else {
      if (l.isSameNode && l.isSameNode(a)) return
      if ((t(a, l, y), d))
        for (var v = 0, E = d.length; v < E; v++) {
          var I = s[d[v]]
          I && g(I, I.parentNode, !1)
        }
    }
    return (
      !y &&
        a !== n &&
        n.parentNode &&
        (a.actualize && (a = a.actualize(n.ownerDocument || M)),
        n.parentNode.replaceChild(a, n)),
      a
    )
  }
}
var Ot = Bt(yt)
const kt = Object.freeze(
    Object.defineProperty(
      { __proto__: null, default: Ot },
      Symbol.toStringTag,
      { value: 'Module' },
    ),
  ),
  Tt = mt(kt)
var Q = {}
Object.defineProperty(Q, '__esModule', { value: !0 })
var Ct = (Q.matches = function (o, n) {
  for (
    var l = (o.document || o.ownerDocument).querySelectorAll(n), p = l.length;
    --p >= 0 && l.item(p) !== o;

  );
  return p > -1
})
Q.selector = function (o) {
  return document.querySelector(o)
}
var Gt = (Q.findAncestor = function (o, n) {
    if (typeof o.closest == 'function') return o.closest(n) || null
    for (; o && o !== document; ) {
      if (Ct(o, n)) return o
      o = o.parentElement
    }
    return null
  }),
  nt = []
Q.on = function (o, n, l, p) {
  var b = arguments.length > 4 && arguments[4] !== void 0 ? arguments[4] : !1,
    N = l.split(' ')
  N.forEach(function (D) {
    var T = function (C) {
      var H = Gt(C.target, n)
      H && ((C.delegateTarget = H), p(C))
    }
    nt.push({ listener: T, element: o, query: n, event: D, capture: b }),
      o.addEventListener(D, T, b)
  })
}
Q.off = function (o, n, l) {
  var p = l.split(' ')
  p.forEach(function (b) {
    nt.forEach(function (N, D) {
      N.element === o &&
        N.query === n &&
        N.event === b &&
        (o.removeEventListener(b, N.listener, N.capture), nt.splice(D, 1))
    })
  })
}
;(function (u, o) {
  Object.defineProperty(o, '__esModule', { value: !0 })
  var n = (function () {
      function A(y, s) {
        for (var d = 0; d < s.length; d++) {
          var m = s[d]
          ;(m.enumerable = m.enumerable || !1),
            (m.configurable = !0),
            'value' in m && (m.writable = !0),
            Object.defineProperty(y, m.key, m)
        }
      }
      return function (y, s, d) {
        return s && A(y.prototype, s), d && A(y, d), y
      }
    })(),
    l = Tt,
    p = N(l),
    b = Q
  function N(A) {
    return A && A.__esModule ? A : { default: A }
  }
  function D(A) {
    if (Array.isArray(A)) {
      for (var y = 0, s = Array(A.length); y < A.length; y++) s[y] = A[y]
      return s
    } else return Array.from(A)
  }
  function T(A, y) {
    if (!(A instanceof y))
      throw new TypeError('Cannot call a class as a function')
  }
  var z =
      'input paste copy click change keydown keyup keypress contextmenu mouseup mousedown mousemove touchstart touchend touchmove compositionstart compositionend focus',
    C = 'input change click',
    H = z.replace(/([a-z]+)/g, '[data-action-$1],') + '[data-action]',
    X = (function () {
      function A(y) {
        var s = this
        T(this, A),
          (this.atemplate = []),
          (this.events = []),
          y &&
            Object.keys(y).forEach(function (e) {
              s[e] = y[e]
            }),
          this.data || (this.data = {}),
          this.templates || (this.templates = [])
        for (
          var d = this.templates, m = d.length, f = 0, g = m;
          f < g;
          f += 1
        ) {
          var _ = this.templates[f],
            h = (0, b.selector)('#' + _).innerHTML
          this.atemplate.push({ id: _, html: h, binded: !1 })
        }
      }
      return (
        n(A, [
          {
            key: 'addDataBind',
            value: function (s) {
              var d = this
              ;(0, b.on)(s, '[data-bind]', C, function (m) {
                var f = m.delegateTarget,
                  g = f.getAttribute('data-bind'),
                  _ = f.getAttribute('href'),
                  h = f.value
                _ && (h = h.replace('#', '')),
                  f.getAttribute('type') === 'checkbox'
                    ? (function () {
                        var e = [],
                          t = document.querySelectorAll(
                            '[data-bind="' + g + '"]',
                          )
                        ;[].forEach.call(t, function (r) {
                          r.checked && e.push(r.value)
                        })
                      })()
                    : f.getAttribute('type') !== 'radio' &&
                      d.updateDataByString(g, h)
              }),
                this.events.push({
                  element: s,
                  selector: '[data-bind]',
                  event: C,
                })
            },
          },
          {
            key: 'addActionBind',
            value: function (s) {
              var d = this
              ;(0, b.on)(s, H, z, function (m) {
                var f = m.delegateTarget,
                  g = z.split(' '),
                  _ = 'action'
                g.forEach(function (i) {
                  f.getAttribute('data-action-' + i) &&
                    m.type === i &&
                    (_ += '-' + i)
                })
                var h = f.getAttribute('data-' + _)
                if (h) {
                  var e = h.replace(/\(.*?\);?/, ''),
                    t = h.replace(/(.*?)\((.*?)\);?/, '$2'),
                    r = t.split(',')
                  if (((d.e = m), d.method && d.method[e])) {
                    var a
                    ;(a = d.method)[e].apply(a, D(r))
                  } else d[e] && d[e].apply(d, D(r))
                }
              }),
                this.events.push({ element: s, selector: H, event: C })
            },
          },
          {
            key: 'removeTemplateEvents',
            value: function () {
              this.events.forEach(function (s) {
                ;(0, b.off)(s.element, s.selector, s.event)
              })
            },
          },
          {
            key: 'addTemplate',
            value: function (s, d) {
              this.atemplate.push({ id: s, html: d, binded: !1 }),
                this.templates.push(s)
            },
          },
          {
            key: 'getData',
            value: function () {
              return JSON.parse(JSON.stringify(this.data))
            },
          },
          {
            key: 'saveData',
            value: function (s) {
              var d = JSON.stringify(this.data)
              localStorage.setItem(s, d)
            },
          },
          {
            key: 'setData',
            value: function (s) {
              var d = this
              Object.keys(s).forEach(function (m) {
                typeof s[m] != 'function' && (d.data[m] = s[m])
              })
            },
          },
          {
            key: 'loadData',
            value: function (s) {
              var d = JSON.parse(localStorage.getItem(s))
              d && this.setData(d)
            },
          },
          {
            key: 'getRand',
            value: function (s, d) {
              return ~~(Math.random() * (d - s + 1)) + s
            },
          },
          {
            key: 'getRandText',
            value: function (s) {
              for (
                var d = '',
                  m =
                    'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789',
                  f = m.length,
                  g = 0;
                g < s;
                g += 1
              )
                d += m.charAt(Math.floor(this.getRand(0, f)))
              return d
            },
          },
          {
            key: 'getDataFromObj',
            value: function (s, d) {
              ;(s = s.replace(/\[([\w\-\.ぁ-んァ-ヶ亜-熙]+)\]/g, '.$1')),
                (s = s.replace(/^\./, ''))
              for (var m = s.split('.'); m.length; ) {
                var f = m.shift()
                if (f in d) d = d[f]
                else return null
              }
              return d
            },
          },
          {
            key: 'getDataByString',
            value: function (s) {
              var d = this.data
              return this.getDataFromObj(s, d)
            },
          },
          {
            key: 'updateDataByString',
            value: function (s, d) {
              for (var m = this.data, f = s.split('.'); f.length > 1; )
                m = m[f.shift()]
              m[f.shift()] = d
            },
          },
          {
            key: 'removeDataByString',
            value: function (s) {
              for (var d = this.data, m = s.split('.'); m.length > 1; )
                d = d[m.shift()]
              var f = m.shift()
              f.match(/^\d+$/) ? d.splice(Number(f), 1) : delete d[f]
            },
          },
          {
            key: 'resolveBlock',
            value: function (s, d, m) {
              var f = this,
                g = s.match(
                  /<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+):touch#([\w\-\.ぁ-んァ-ヶ亜-熙]+) -->/g,
                ),
                _ = s.match(
                  /<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+):touchnot#([\w\-\.ぁ-んァ-ヶ亜-熙]+) -->/g,
                ),
                h = s.match(/<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+):exist -->/g),
                e = s.match(/<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+):empty -->/g)
              if (g)
                for (var t = 0, r = g.length; t < r; t += 1) {
                  var a = g[t]
                  a = a.replace(
                    /([\w\-\.ぁ-んァ-ヶ亜-熙]+):touch#([\w\-\.ぁ-んァ-ヶ亜-熙]+)/,
                    '($1):touch#($2)',
                  )
                  var i = a.replace(/BEGIN/, 'END'),
                    c = new RegExp(a + '(([\\n\\r\\t]|.)*?)' + i, 'g')
                  s = s.replace(c, function (V, k, F, L) {
                    var j =
                      typeof d[k] == 'function'
                        ? d[k].apply(f)
                        : f.getDataFromObj(k, d)
                    return '' + j === F ? L : ''
                  })
                }
              if (_)
                for (var v = 0, E = _.length; v < E; v += 1) {
                  var I = _[v]
                  I = I.replace(
                    /([\w\-\.ぁ-んァ-ヶ亜-熙]+):touchnot#([\w\-\.ぁ-んァ-ヶ亜-熙]+)/,
                    '($1):touchnot#($2)',
                  )
                  var x = I.replace(/BEGIN/, 'END'),
                    w = new RegExp(I + '(([\\n\\r\\t]|.)*?)' + x, 'g')
                  s = s.replace(w, function (V, k, F, L) {
                    var j =
                      typeof d[k] == 'function'
                        ? d[k].apply(f)
                        : f.getDataFromObj(k, d)
                    return '' + j !== F ? L : ''
                  })
                }
              if (h)
                for (var P = 0, S = h.length; P < S; P += 1) {
                  var O = h[P]
                  O = O.replace(
                    /([\w\-\.ぁ-んァ-ヶ亜-熙]+):exist/,
                    '($1):exist',
                  )
                  var G = O.replace(/BEGIN/, 'END'),
                    U = new RegExp(O + '(([\\n\\r\\t]|.)*?)' + G, 'g')
                  s = s.replace(U, function (V, k, F) {
                    var L =
                      typeof d[k] == 'function'
                        ? d[k].apply(f)
                        : f.getDataFromObj(k, d)
                    return L || L === 0 ? F : ''
                  })
                }
              if (e)
                for (var q = 0, Y = e.length; q < Y; q += 1) {
                  var R = e[q]
                  R = R.replace(
                    /([\w\-\.ぁ-んァ-ヶ亜-熙]+):empty/,
                    '($1):empty',
                  )
                  var W = R.replace(/BEGIN/, 'END'),
                    Z = new RegExp(R + '(([\\n\\r\\t]|.)*?)' + W, 'g')
                  s = s.replace(Z, function (V, k, F) {
                    var L =
                      typeof d[k] == 'function'
                        ? d[k].apply(f)
                        : f.getDataFromObj(k, d)
                    return !L && L !== 0 ? F : ''
                  })
                }
              return (
                (s = s.replace(
                  /{([\w\-\.ぁ-んァ-ヶ亜-熙]+)}(\[([\w\-\.ぁ-んァ-ヶ亜-熙]+)\])*/g,
                  function (V, k, F, L) {
                    var j = void 0
                    if ('' + k == 'i') j = m
                    else if (d[k] || d[k] === 0)
                      typeof d[k] == 'function'
                        ? (j = d[k].apply(f))
                        : (j = d[k])
                    else
                      return L && f.convert && f.convert[L]
                        ? f.convert[L].call(f, '')
                        : ''
                    return L && f.convert && f.convert[L]
                      ? f.convert[L].call(f, j)
                      : j
                  },
                )),
                s
              )
            },
          },
          {
            key: 'resolveAbsBlock',
            value: function (s) {
              var d = this
              return (
                (s = s.replace(/{(.*?)}/g, function (m, f) {
                  var g = d.getDataByString(f)
                  return typeof g < 'u'
                    ? typeof g == 'function'
                      ? g.apply(d)
                      : g
                    : m
                })),
                s
              )
            },
          },
          {
            key: 'resolveInclude',
            value: function (s) {
              var d = /<!-- #include id="(.*?)" -->/g
              return (
                (s = s.replace(d, function (m, f) {
                  return (0, b.selector)('#' + f).innerHTML
                })),
                s
              )
            },
          },
          {
            key: 'resolveWith',
            value: function (s) {
              var d =
                /<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+):with -->(([\n\r\t]|.)*?)<!-- END ([\w\-\.ぁ-んァ-ヶ亜-熙]+):with -->/g
              return (
                (s = s.replace(d, function (m, f) {
                  return (
                    (m = m.replace(
                      /data\-bind=['"](.*?)['"]/g,
                      "data-bind='" + f + ".$1'",
                    )),
                    m
                  )
                })),
                s
              )
            },
          },
          {
            key: 'resolveLoop',
            value: function (s) {
              var d =
                  /<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+?):loop -->(([\n\r\t]|.)*?)<!-- END ([\w\-\.ぁ-んァ-ヶ亜-熙]+?):loop -->/g,
                m = this
              return (
                (s = s.replace(d, function (f, g, _) {
                  var h = m.getDataByString(g),
                    e = []
                  typeof h == 'function' ? (e = h.apply(m)) : (e = h)
                  var t = ''
                  if (e instanceof Array)
                    for (var r = 0, a = e.length; r < a; r += 1)
                      t += m.resolveBlock(_, e[r], r)
                  return (t = t.replace(/\\([^\\])/g, '$1')), t
                })),
                s
              )
            },
          },
          {
            key: 'removeData',
            value: function (s) {
              var d = this.data
              return (
                Object.keys(d).forEach(function (m) {
                  for (var f = 0, g = s.length; f < g; f += 1)
                    m === s[f] && delete d[m]
                }),
                this
              )
            },
          },
          {
            key: 'hasLoop',
            value: function (s) {
              var d =
                /<!-- BEGIN ([\w\-\.ぁ-んァ-ヶ亜-熙]+?):loop -->(([\n\r\t]|.)*?)<!-- END ([\w\-\.ぁ-んァ-ヶ亜-熙]+?):loop -->/g
              return !!s.match(d)
            },
          },
          {
            key: 'getHtml',
            value: function (s, d) {
              var m = this.atemplate.find(function (_) {
                  return _.id === s
                }),
                f = ''
              if ((m && m.html && (f = m.html), d && (f = s), !f)) return ''
              var g = this.data
              for (
                f = this.resolveInclude(f), f = this.resolveWith(f);
                this.hasLoop(f);

              )
                f = this.resolveLoop(f)
              return (
                (f = this.resolveBlock(f, g)),
                (f = f.replace(/\\([^\\])/g, '$1')),
                (f = this.resolveAbsBlock(f)),
                f.replace(/^([\t ])*\n/gm, '')
              )
            },
          },
          {
            key: 'update',
            value: function () {
              var s = this,
                d =
                  arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : 'html',
                m = arguments[1],
                f = this.templates
              this.beforeUpdated && this.beforeUpdated()
              for (
                var g = function (t, r) {
                    var a = f[t],
                      i = '#' + a,
                      c = s.getHtml(a),
                      v = (0, b.selector)("[data-id='" + a + "']")
                    if (!v)
                      (0, b.selector)(i).insertAdjacentHTML(
                        'afterend',
                        '<div data-id="' + a + '"></div>',
                      ),
                        d === 'text'
                          ? ((0, b.selector)(
                              "[data-id='" + a + "']",
                            ).innerText = c)
                          : ((0, b.selector)(
                              "[data-id='" + a + "']",
                            ).innerHTML = c)
                    else if (d === 'text') v.innerText = c
                    else if (m) {
                      var E = document.createElement('div')
                      E.innerHTML = c
                      var I = E.querySelector(m).outerHTML
                      ;(0, p.default)(v.querySelector(m), I)
                    } else
                      (0, p.default)(
                        v,
                        "<div data-id='" + a + "'>" + c + '</div>',
                      )
                    var x = s.atemplate.find(function (w) {
                      return w.id === a
                    })
                    x.binded ||
                      ((x.binded = !0),
                      s.addDataBind((0, b.selector)("[data-id='" + a + "']")),
                      s.addActionBind((0, b.selector)("[data-id='" + a + "']")))
                  },
                  _ = 0,
                  h = f.length;
                _ < h;
                _ += 1
              )
                g(_)
              return (
                this.updateBindingData(m),
                this.onUpdated && this.onUpdated(m),
                this
              )
            },
          },
          {
            key: 'updateBindingData',
            value: function (s) {
              for (
                var d = this, m = this.templates, f = 0, g = m.length;
                f < g;
                f += 1
              ) {
                var _ = m[f],
                  h = (0, b.selector)("[data-id='" + _ + "']")
                s && (h = h.querySelector(s))
                var e = h.querySelectorAll('[data-bind]')
                ;[].forEach.call(e, function (r) {
                  var a = d.getDataByString(r.getAttribute('data-bind'))
                  r.getAttribute('type') === 'checkbox' ||
                  r.getAttribute('type') === 'radio'
                    ? a === r.value && (r.checked = !0)
                    : (r.value = a)
                })
                var t = h.querySelectorAll('[data-bind-oneway]')
                ;[].forEach.call(t, function (r) {
                  var a = d.getDataByString(r.getAttribute('data-bind-oneway'))
                  r.getAttribute('type') === 'checkbox' ||
                  r.getAttribute('type') === 'radio'
                    ? a === r.value && (r.checked = !0)
                    : (r.value = a)
                })
              }
              return this
            },
          },
          {
            key: 'applyMethod',
            value: function (s) {
              for (
                var d,
                  m = arguments.length,
                  f = Array(m > 1 ? m - 1 : 0),
                  g = 1;
                g < m;
                g++
              )
                f[g - 1] = arguments[g]
              return (d = this.method)[s].apply(d, f)
            },
          },
          {
            key: 'getComputedProp',
            value: function (s) {
              return this.data[s].apply(this)
            },
          },
          {
            key: 'remove',
            value: function (s) {
              for (var d = this.data, m = s.split('.'); m.length > 1; )
                d = d[m.shift()]
              var f = m.shift()
              return (
                f.match(/^\d+$/) ? d.splice(Number(f), 1) : delete d[f], this
              )
            },
          },
        ]),
        A
      )
    })()
  ;(o.default = X), (u.exports = o.default)
})(ot, ot.exports)
var Lt = ot.exports
try {
  var ut = new window.CustomEvent('test')
  if ((ut.preventDefault(), ut.defaultPrevented !== !0))
    throw new Error('Could not prevent default')
} catch {
  var ct = function (o, n) {
    var l, p
    return (
      (n = n || { bubbles: !1, cancelable: !1, detail: void 0 }),
      (l = document.createEvent('CustomEvent')),
      l.initCustomEvent(o, n.bubbles, n.cancelable, n.detail),
      (p = l.preventDefault),
      (l.preventDefault = function () {
        p.call(this)
        try {
          Object.defineProperty(this, 'defaultPrevented', {
            get: function () {
              return !0
            },
          })
        } catch {
          this.defaultPrevented = !0
        }
      }),
      l
    )
  }
  ;(ct.prototype = window.Event.prototype), (window.CustomEvent = ct)
}
var B = {}
Object.defineProperty(B, '__esModule', { value: !0 })
B.isOldIE =
  B.getBrowser =
  B.removeClass =
  B.addClass =
  B.append =
  B.removeElement =
  B.getViewPos =
  B.parseQuery =
  B.triggerEvent =
  B.extend =
  B.isSmartPhone =
    void 0
function et(u) {
  return (
    typeof Symbol == 'function' && typeof Symbol.iterator == 'symbol'
      ? (et = function (n) {
          return typeof n
        })
      : (et = function (n) {
          return n &&
            typeof Symbol == 'function' &&
            n.constructor === Symbol &&
            n !== Symbol.prototype
            ? 'symbol'
            : typeof n
        }),
    et(u)
  )
}
var Ht = function () {
  var o = navigator.userAgent
  return (
    o.indexOf('iPhone') > 0 ||
    o.indexOf('iPad') > 0 ||
    o.indexOf('ipod') > 0 ||
    o.indexOf('Android') > 0
  )
}
B.isSmartPhone = Ht
function ft(u) {
  u = u || {}
  for (var o = 1; o < arguments.length; o++) {
    var n = arguments[o]
    if (n)
      for (var l in n)
        n.hasOwnProperty(l) &&
          (et(n[l]) === 'object' ? (u[l] = ft(u[l], n[l])) : (u[l] = n[l]))
  }
  return u
}
var zt = ft
B.extend = zt
var Xt = function (o, n, l) {
  var p
  window.CustomEvent
    ? (p = new CustomEvent(n, { cancelable: !0 }))
    : ((p = document.createEvent('CustomEvent')),
      p.initCustomEvent(n, !1, !1, l)),
    o.dispatchEvent(p)
}
B.triggerEvent = Xt
var Mt = function (o) {
  for (var n = o.split('&'), l = {}, p = 0, b = n.length, N, D, T; p < b; p++)
    (N = n[p].split('=')),
      N[0] !== void 0 &&
        ((D = N[0]),
        (T = N[1] !== void 0 ? N.slice(1).join('=') : D),
        (l[D] = decodeURIComponent(T)))
  return l
}
B.parseQuery = Mt
var Ut = function (o) {
  return {
    left: o.getBoundingClientRect().left,
    top: o.getBoundingClientRect().top,
  }
}
B.getViewPos = Ut
var Yt = function (o) {
  o && o.parentNode && o.parentNode.removeChild(o)
}
B.removeElement = Yt
var Wt = function (o, n) {
  var l = document.createElement('div')
  for (l.innerHTML = n; l.children.length > 0; ) o.appendChild(l.children[0])
}
B.append = Wt
var Rt = function (o, n) {
  o.classList ? o.classList.add(n) : (o.className += ' '.concat(n))
}
B.addClass = Rt
var jt = function (o, n) {
  o.classList
    ? o.classList.remove(n)
    : (o.className = o.className.replace(
        new RegExp('(^|\\b)' + n.split(' ').join('|') + '(\\b|$)', 'gi'),
        ' ',
      ))
}
B.removeClass = jt
var vt = function () {
  var o = window.navigator.userAgent.toLowerCase(),
    n = window.navigator.appVersion.toLowerCase(),
    l = 'unknown'
  return (
    o.indexOf('msie') != -1
      ? n.indexOf('msie 6.') != -1
        ? (l = 'ie6')
        : n.indexOf('msie 7.') != -1
        ? (l = 'ie7')
        : n.indexOf('msie 8.') != -1
        ? (l = 'ie8')
        : n.indexOf('msie 9.') != -1
        ? (l = 'ie9')
        : n.indexOf('msie 10.') != -1
        ? (l = 'ie10')
        : (l = 'ie')
      : o.indexOf('trident/7') != -1
      ? (l = 'ie11')
      : o.indexOf('chrome') != -1
      ? (l = 'chrome')
      : o.indexOf('safari') != -1
      ? (l = 'safari')
      : o.indexOf('opera') != -1
      ? (l = 'opera')
      : o.indexOf('firefox') != -1 && (l = 'firefox'),
    l
  )
}
B.getBrowser = vt
var qt = function () {
  var o = vt()
  return o.indexOf('ie') !== -1 && parseInt(o.replace(/[^0-9]/g, '')) <= 10
}
B.isOldIE = qt
var pt = {}
;(function (u) {
  ;(function (o) {
    var n = o.Promise,
      l =
        n &&
        'resolve' in n &&
        'reject' in n &&
        'all' in n &&
        'race' in n &&
        (function () {
          var a
          return (
            new n(function (i) {
              a = i
            }),
            typeof a == 'function'
          )
        })()
    u ? ((u.Promise = l ? n : r), (u.Polyfill = r)) : l || (o.Promise = r)
    var p = 'pending',
      b = 'sealed',
      N = 'fulfilled',
      D = 'rejected',
      T = function () {}
    function z(a) {
      return Object.prototype.toString.call(a) === '[object Array]'
    }
    var C = typeof setImmediate < 'u' ? setImmediate : setTimeout,
      H = [],
      X
    function A() {
      for (var a = 0; a < H.length; a++) H[a][0](H[a][1])
      ;(H = []), (X = !1)
    }
    function y(a, i) {
      H.push([a, i]), X || ((X = !0), C(A, 0))
    }
    function s(a, i) {
      function c(E) {
        f(i, E)
      }
      function v(E) {
        _(i, E)
      }
      try {
        a(c, v)
      } catch (E) {
        v(E)
      }
    }
    function d(a) {
      var i = a.owner,
        c = i.state_,
        v = i.data_,
        E = a[c],
        I = a.then
      if (typeof E == 'function') {
        c = N
        try {
          v = E(v)
        } catch (x) {
          _(I, x)
        }
      }
      m(I, v) || (c === N && f(I, v), c === D && _(I, v))
    }
    function m(a, i) {
      var c
      try {
        if (a === i)
          throw new TypeError(
            'A promises callback cannot return that same promise.',
          )
        if (i && (typeof i == 'function' || typeof i == 'object')) {
          var v = i.then
          if (typeof v == 'function')
            return (
              v.call(
                i,
                function (E) {
                  c || ((c = !0), i !== E ? f(a, E) : g(a, E))
                },
                function (E) {
                  c || ((c = !0), _(a, E))
                },
              ),
              !0
            )
        }
      } catch (E) {
        return c || _(a, E), !0
      }
      return !1
    }
    function f(a, i) {
      ;(a === i || !m(a, i)) && g(a, i)
    }
    function g(a, i) {
      a.state_ === p && ((a.state_ = b), (a.data_ = i), y(e, a))
    }
    function _(a, i) {
      a.state_ === p && ((a.state_ = b), (a.data_ = i), y(t, a))
    }
    function h(a) {
      var i = a.then_
      a.then_ = void 0
      for (var c = 0; c < i.length; c++) d(i[c])
    }
    function e(a) {
      ;(a.state_ = N), h(a)
    }
    function t(a) {
      ;(a.state_ = D), h(a)
    }
    function r(a) {
      if (typeof a != 'function')
        throw new TypeError('Promise constructor takes a function argument')
      if (!(this instanceof r))
        throw new TypeError(
          "Failed to construct 'Promise': Please use the 'new' operator, this object constructor cannot be called as a function.",
        )
      ;(this.then_ = []), s(a, this)
    }
    ;(r.prototype = {
      constructor: r,
      state_: p,
      then_: null,
      data_: void 0,
      then: function (a, i) {
        var c = {
          owner: this,
          then: new this.constructor(T),
          fulfilled: a,
          rejected: i,
        }
        return (
          this.state_ === N || this.state_ === D ? y(d, c) : this.then_.push(c),
          c.then
        )
      },
      catch: function (a) {
        return this.then(null, a)
      },
    }),
      (r.all = function (a) {
        var i = this
        if (!z(a))
          throw new TypeError('You must pass an array to Promise.all().')
        return new i(function (c, v) {
          var E = [],
            I = 0
          function x(S) {
            return (
              I++,
              function (O) {
                ;(E[S] = O), --I || c(E)
              }
            )
          }
          for (var w = 0, P; w < a.length; w++)
            (P = a[w]),
              P && typeof P.then == 'function' ? P.then(x(w), v) : (E[w] = P)
          I || c(E)
        })
      }),
      (r.race = function (a) {
        var i = this
        if (!z(a))
          throw new TypeError('You must pass an array to Promise.race().')
        return new i(function (c, v) {
          for (var E = 0, I; E < a.length; E++)
            (I = a[E]), I && typeof I.then == 'function' ? I.then(c, v) : c(I)
        })
      }),
      (r.resolve = function (a) {
        var i = this
        return a && typeof a == 'object' && a.constructor === i
          ? a
          : new i(function (c) {
              c(a)
            })
      }),
      (r.reject = function (a) {
        var i = this
        return new i(function (c, v) {
          v(a)
        })
      })
  })(
    typeof window < 'u'
      ? window
      : typeof at < 'u'
      ? at
      : typeof self < 'u'
      ? self
      : at,
  )
})(pt)
;(function (u, o) {
  Object.defineProperty(o, '__esModule', { value: !0 }), (o.default = void 0)
  var n = l(Lt)
  function l(g) {
    return g && g.__esModule ? g : { default: g }
  }
  function p(g) {
    return (
      typeof Symbol == 'function' && typeof Symbol.iterator == 'symbol'
        ? (p = function (h) {
            return typeof h
          })
        : (p = function (h) {
            return h &&
              typeof Symbol == 'function' &&
              h.constructor === Symbol &&
              h !== Symbol.prototype
              ? 'symbol'
              : typeof h
          }),
      p(g)
    )
  }
  function b(g, _) {
    if (!(g instanceof _))
      throw new TypeError('Cannot call a class as a function')
  }
  function N(g, _) {
    for (var h = 0; h < _.length; h++) {
      var e = _[h]
      ;(e.enumerable = e.enumerable || !1),
        (e.configurable = !0),
        'value' in e && (e.writable = !0),
        Object.defineProperty(g, e.key, e)
    }
  }
  function D(g, _, h) {
    return _ && N(g.prototype, _), g
  }
  function T(g, _) {
    return _ && (p(_) === 'object' || typeof _ == 'function') ? _ : z(g)
  }
  function z(g) {
    if (g === void 0)
      throw new ReferenceError(
        "this hasn't been initialised - super() hasn't been called",
      )
    return g
  }
  function C(g) {
    return (
      (C = Object.setPrototypeOf
        ? Object.getPrototypeOf
        : function (h) {
            return h.__proto__ || Object.getPrototypeOf(h)
          }),
      C(g)
    )
  }
  function H(g, _) {
    if (typeof _ != 'function' && _ !== null)
      throw new TypeError('Super expression must either be null or a function')
    ;(g.prototype = Object.create(_ && _.prototype, {
      constructor: { value: g, writable: !0, configurable: !0 },
    })),
      _ && X(g, _)
  }
  function X(g, _) {
    return (
      (X =
        Object.setPrototypeOf ||
        function (e, t) {
          return (e.__proto__ = t), e
        }),
      X(g, _)
    )
  }
  var A = `<div class="\\{classNames.smartPhoto\\}"<!-- BEGIN hide:exist --> aria-hidden="true"<!-- END hide:exist --><!-- BEGIN hide:empty --> aria-hidden="false"<!-- END hide:empty --> role="dialog">
	<div class="\\{classNames.smartPhotoBody\\}">
		<div class="\\{classNames.smartPhotoInner\\}">
			   <div class="\\{classNames.smartPhotoHeader\\}">
					<span class="\\{classNames.smartPhotoCount\\}">{currentIndex}[increment]/{total}</span>
					<span class="\\{classNames.smartPhotoCaption\\}" aria-live="polite" tabindex="-1"><!-- BEGIN groupItems:loop --><!-- \\BEGIN currentIndex:touch#{index} -->{caption}<!-- \\END currentIndex:touch#{index} --><!-- END groupItems:loop --></span>
					<button class="\\{classNames.smartPhotoDismiss\\}" data-action-click="hidePhoto()"><span class="smartphoto-sr-only">\\{message.closeDialog\\}</span></button>
				</div>
				<div class="\\{classNames.smartPhotoContent\\}"<!-- BEGIN isSmartPhone:exist --> data-action-touchstart="beforeDrag" data-action-touchmove="onDrag" data-action-touchend="afterDrag(false)"<!-- END isSmartPhone:exist --><!-- BEGIN isSmartPhone:empty --> data-action-click="hidePhoto()"<!-- END isSmartPhone:empty -->>
				</div>
				<ul style="transform:translate({translateX}[round]px,{translateY}[round]px);" class="\\{classNames.smartPhotoList\\}<!-- BEGIN onMoveClass:exist --> \\{classNames.smartPhotoListOnMove\\}<!-- END onMoveClass:exist -->">
					<!-- BEGIN groupItems:loop -->
					<li style="transform:translate({translateX}[round]px,{translateY}[round]px);" class="<!-- \\BEGIN currentIndex:touch#{index} -->current<!-- \\END currentIndex:touch#{index} -->">
						<!-- BEGIN processed:exist -->
						<div style="transform:translate({x}[round]px,{y}[round]px) scale({scale});" class="\\\\{classNames.smartPhotoImgWrap\\\\}"<!-- \\BEGIN isSmartPhone:empty --> data-action-mousemove="onDrag" data-action-mousedown="beforeDrag" data-action-mouseup="afterDrag"<!-- \\END isSmartPhone:empty --><!-- \\BEGIN isSmartPhone:exist --> data-action-touchstart="beforeDrag" data-action-touchmove="onDrag" data-action-touchend="afterDrag"<!-- \\END isSmartPhone:exist -->>
							<img style="<!-- \\BEGIN currentIndex:touch#{index} -->transform:translate(\\{photoPosX\\}[virtualPos]px,\\{photoPosY\\}[virtualPos]px) scale(\\{scaleSize\\});<!-- \\END currentIndex:touch#{index} -->width:{width}px;" src="{src}" class="\\\\{classNames.smartPhotoImg\\\\}<!-- \\BEGIN scale:exist -->  \\\\{classNames.smartPhotoImgOnMove\\\\}<!-- \\END scale:exist --><!-- \\BEGIN elastic:exist --> \\\\{classNames.smartPhotoImgElasticMove\\\\}<!-- \\END elastic:exist --><!-- \\BEGIN appear:exist --> active<!-- \\END appear:exist -->" ondragstart="return false;">
						</div>
						<!-- END processed:exist -->
						<!-- BEGIN processed:empty -->
						<div class="\\\\{classNames.smartPhotoLoaderWrap\\\\}">
							<span class="\\\\{classNames.smartPhotoLoader\\\\}"></span>
						</div>
						<!-- END processed:empty -->
					</li>
					<!-- END groupItems:loop -->
				</ul>
				<!-- BEGIN arrows:exist -->
				<ul class="\\{classNames.smartPhotoArrows\\}"<!-- BEGIN hideUi:exist --> aria-hidden="true"<!-- END hideUi:exist --><!-- BEGIN hideUi:exist --> aria-hidden="false"<!-- END hideUi:exist -->>
					<li class="\\{classNames.smartPhotoArrowLeft\\}<!-- BEGIN isSmartPhone:exist --> \\{classNames.smartPhotoArrowHideIcon\\}<!-- END isSmartPhone:exist -->"<!-- BEGIN showPrevArrow:empty --> aria-hidden="true"<!-- END showPrevArrow:empty -->><a href="#" data-action-click="gotoSlide({prev})" role="button"><span class="smartphoto-sr-only">\\{message.gotoPrevImage\\}</span></a></li>
					<li class="\\{classNames.smartPhotoArrowRight\\}<!-- BEGIN isSmartPhone:exist --> \\{classNames.smartPhotoArrowHideIcon\\}<!-- END isSmartPhone:exist -->"<!-- BEGIN showNextArrow:empty --> aria-hidden="true"<!-- END showNextArrow:empty -->><a href="#" data-action-click="gotoSlide({next})" role="button"><span class="smartphoto-sr-only">\\{message.gotoNextImage\\}</span></a></li>
				</ul>
				<!-- END arrows:exist -->
				<!-- BEGIN nav:exist -->
				<nav class="\\{classNames.smartPhotoNav\\}"<!-- BEGIN hideUi:exist --> aria-hidden="true"<!-- END hideUi:exist --><!-- BEGIN hideUi:exist --> aria-hidden="false"<!-- END hideUi:exist -->>
					<ul>
						<!-- BEGIN groupItems:loop -->
						<li><a href="#" data-action-click="gotoSlide({index})" class="<!-- \\BEGIN currentIndex:touch#{index} -->current<!-- \\END currentIndex:touch#{index} -->" style="background-image:url('{thumb}');" role="button"><span class="smartphoto-sr-only">go to {caption}</span></a></li>
						<!-- END groupItems:loop -->
					</ul>
				</nav>
				<!-- END nav:exist -->
		</div>
		<!-- BEGIN appearEffect:exist -->
		<img src=\\{appearEffect.img\\}
		class="\\{classNames.smartPhotoImgClone\\}"
		style="width:\\{appearEffect.width\\}px;height:\\{appearEffect.height\\}px;transform:translate(\\{appearEffect.left\\}px,\\{appearEffect.top\\}px) scale(1)" />
		<!-- END appearEffect:exist -->
	</div>
</div>
`,
    y = B,
    s = pt,
    d = s.Promise,
    m = {
      classNames: {
        smartPhoto: 'smartphoto',
        smartPhotoClose: 'smartphoto-close',
        smartPhotoBody: 'smartphoto-body',
        smartPhotoInner: 'smartphoto-inner',
        smartPhotoContent: 'smartphoto-content',
        smartPhotoImg: 'smartphoto-img',
        smartPhotoImgOnMove: 'smartphoto-img-onmove',
        smartPhotoImgElasticMove: 'smartphoto-img-elasticmove',
        smartPhotoImgWrap: 'smartphoto-img-wrap',
        smartPhotoArrows: 'smartphoto-arrows',
        smartPhotoNav: 'smartphoto-nav',
        smartPhotoArrowRight: 'smartphoto-arrow-right',
        smartPhotoArrowLeft: 'smartphoto-arrow-left',
        smartPhotoArrowHideIcon: 'smartphoto-arrow-hide',
        smartPhotoImgLeft: 'smartphoto-img-left',
        smartPhotoImgRight: 'smartphoto-img-right',
        smartPhotoList: 'smartphoto-list',
        smartPhotoListOnMove: 'smartphoto-list-onmove',
        smartPhotoHeader: 'smartphoto-header',
        smartPhotoCount: 'smartphoto-count',
        smartPhotoCaption: 'smartphoto-caption',
        smartPhotoDismiss: 'smartphoto-dismiss',
        smartPhotoLoader: 'smartphoto-loader',
        smartPhotoLoaderWrap: 'smartphoto-loader-wrap',
        smartPhotoImgClone: 'smartphoto-img-clone',
      },
      message: {
        gotoNextImage: 'go to the next image',
        gotoPrevImage: 'go to the previous image',
        closeDialog: 'close the image dialog',
      },
      arrows: !0,
      nav: !0,
      showAnimation: !0,
      verticalGravity: !1,
      useOrientationApi: !1,
      useHistoryApi: !0,
      swipeTopToClose: !1,
      swipeBottomToClose: !0,
      swipeOffset: 100,
      headerHeight: 60,
      footerHeight: 60,
      forceInterval: 10,
      registance: 0.5,
      loadOffset: 2,
      resizeStyle: 'fit',
      lazyAttribute: 'data-src',
    },
    f = (function (g) {
      H(_, g)
      function _(h, e) {
        var t
        b(this, _),
          (t = T(this, C(_).call(this))),
          (t.data = y.extend({}, m, e)),
          (t.data.currentIndex = 0),
          (t.data.oldIndex = 0),
          (t.data.hide = !0),
          (t.data.group = {}),
          (t.data.scaleSize = 1),
          (t.data.scale = !1),
          (t.pos = { x: 0, y: 0 }),
          (t.data.photoPosX = 0),
          (t.data.photoPosY = 0),
          (t.handlers = []),
          (t.convert = {
            increment: t.increment,
            virtualPos: t.virtualPos,
            round: t.round,
          }),
          (t.data.groupItems = t.groupItems),
          (t.elements = typeof h == 'string' ? document.querySelectorAll(h) : h)
        var r = new Date()
        ;(t.tapSecond = r.getTime()),
          (t.onListMove = !1),
          (t.clicked = !1),
          (t.id = t._getUniqId()),
          (t.vx = 0),
          (t.vy = 0),
          (t.data.appearEffect = null),
          t.addTemplate(t.id, A),
          (t.data.isSmartPhone = t._isSmartPhone())
        var a = document.querySelector('body')
        y.append(a, "<div data-id='".concat(t.id, "'></div>")),
          [].forEach.call(t.elements, function (x) {
            t.addNewItem(x)
          }),
          t.update()
        var i = t._getCurrentItemByHash()
        if (
          (i && y.triggerEvent(i.element, 'click'),
          (t.interval = setInterval(function () {
            t._doAnim()
          }, t.data.forceInterval)),
          !t.data.isSmartPhone)
        ) {
          var c = function () {
              t.groupItems() &&
                (t._resetTranslate(),
                t._setPosByCurrentIndex(),
                t._setSizeByScreen(),
                t.update())
            },
            v = function (w) {
              var P = w.keyCode || w.which
              t.data.hide !== !0 &&
                (P === 37
                  ? t.gotoSlide(t.data.prev)
                  : P === 39
                  ? t.gotoSlide(t.data.next)
                  : P === 27 && t.hidePhoto())
            }
          return (
            window.addEventListener('resize', c),
            window.addEventListener('keydown', v),
            t._registerRemoveEvent(window, 'resize', c),
            t._registerRemoveEvent(window, 'keydown', v),
            T(t)
          )
        }
        var E = function () {
          if (t.groupItems()) {
            t._resetTranslate(),
              t._setPosByCurrentIndex(),
              t._setHashByCurrentIndex(),
              t._setSizeByScreen(),
              t.update()
            var w = t._getWindowWidth(),
              P = 500,
              S = function O(G) {
                new d(function (U) {
                  setTimeout(function () {
                    U()
                  }, 25)
                }).then(function () {
                  w !== t._getWindowWidth()
                    ? (t._resetTranslate(),
                      t._setPosByCurrentIndex(),
                      t._setHashByCurrentIndex(),
                      t._setSizeByScreen(),
                      t.update())
                    : G <= P && O(G + 25)
                })
              }
            S(0)
          }
        }
        if (
          (window.addEventListener('orientationchange', E),
          t._registerRemoveEvent(window, 'orientationchange', E),
          !t.data.useOrientationApi)
        )
          return T(t)
        var I = function (w) {
          var P = window,
            S = P.orientation
          !w ||
            !w.gamma ||
            t.data.appearEffect ||
            (!t.isBeingZoomed &&
              !t.photoSwipable &&
              !t.data.elastic &&
              t.data.scale &&
              (S === 0
                ? t._calcGravity(w.gamma, w.beta)
                : S === 90
                ? t._calcGravity(w.beta, w.gamma)
                : S === -90
                ? t._calcGravity(-w.beta, -w.gamma)
                : S === 180 && t._calcGravity(-w.gamma, -w.beta)))
        }
        return (
          window.addEventListener('deviceorientation', I),
          t._registerRemoveEvent(window, 'deviceorientation', I),
          t
        )
      }
      return (
        D(_, [
          {
            key: 'on',
            value: function (e, t) {
              var r = this._getElementByClass(this.data.classNames.smartPhoto),
                a = function (c) {
                  t.call(r, c)
                }
              r.addEventListener(e, a), this._registerRemoveEvent(r, e, a)
            },
          },
          {
            key: '_registerRemoveEvent',
            value: function (e, t, r) {
              this.handlers.push({ target: e, event: t, handler: r })
            },
          },
          {
            key: 'destroy',
            value: function () {
              this.handlers.forEach(function (t) {
                t.target.removeEventListener(t.event, t.handler)
              })
              var e = document.querySelector('[data-id="'.concat(this.id, '"]'))
              y.removeElement(e),
                clearInterval(this.interval),
                this.removeTemplateEvents()
            },
          },
          {
            key: 'increment',
            value: function (e) {
              return e + 1
            },
          },
          {
            key: 'round',
            value: function (e) {
              return Math.round(e)
            },
          },
          {
            key: 'virtualPos',
            value: function (e) {
              e = parseInt(e, 10)
              var t = this._getSelectedItem()
              return e / t.scale / this.data.scaleSize
            },
          },
          {
            key: 'groupItems',
            value: function () {
              return this.data.group[this.data.currentGroup]
            },
          },
          {
            key: '_resetTranslate',
            value: function () {
              var e = this,
                t = this.groupItems()
              t.forEach(function (r, a) {
                r.translateX = e._getWindowWidth() * a
              })
            },
          },
          {
            key: 'addNewItem',
            value: function (e) {
              var t = this,
                r = e.getAttribute('data-group') || 'nogroup',
                a = this.data.group
              r === 'nogroup' && e.setAttribute('data-group', 'nogroup'),
                a[r] || (a[r] = [])
              var i = a[r].length,
                c = document.querySelector('body'),
                v = e.getAttribute('href'),
                E = e.querySelector('img'),
                I = v
              E &&
                (E.getAttribute(this.data.lazyAttribute)
                  ? (I = E.getAttribute(this.data.lazyAttribute))
                  : E.currentSrc
                  ? (I = E.currentSrc)
                  : (I = E.src))
              var x = {
                src: v,
                thumb: I,
                caption: e.getAttribute('data-caption'),
                groupId: r,
                translateX: this._getWindowWidth() * i,
                index: i,
                translateY: 0,
                width: 50,
                height: 50,
                id: e.getAttribute('data-id') || i,
                loaded: !1,
                processed: !1,
                element: e,
              }
              a[r].push(x), (this.data.currentGroup = r)
              var w = e.getAttribute('data-id')
              w || e.setAttribute('data-id', i), e.setAttribute('data-index', i)
              var P = function (O) {
                O.preventDefault(),
                  (t.data.currentGroup = e.getAttribute('data-group')),
                  (t.data.currentIndex = parseInt(
                    e.getAttribute('data-index'),
                    10,
                  )),
                  t._setHashByCurrentIndex()
                var G = t._getSelectedItem()
                G.loaded
                  ? (t._initPhoto(),
                    t.addAppearEffect(e, G),
                    (t.clicked = !0),
                    t.update(),
                    (c.style.overflow = 'hidden'),
                    t._fireEvent('open'))
                  : t._loadItem(G).then(function () {
                      t._initPhoto(),
                        t.addAppearEffect(e, G),
                        (t.clicked = !0),
                        t.update(),
                        (c.style.overflow = 'hidden'),
                        t._fireEvent('open')
                    })
              }
              e.addEventListener('click', P),
                this._registerRemoveEvent(e, 'click', P)
            },
          },
          {
            key: '_initPhoto',
            value: function () {
              ;(this.data.total = this.groupItems().length),
                (this.data.hide = !1),
                (this.data.photoPosX = 0),
                (this.data.photoPosY = 0),
                this._setPosByCurrentIndex(),
                this._setSizeByScreen(),
                this.setArrow(),
                this.data.resizeStyle === 'fill' &&
                  this.data.isSmartPhone &&
                  ((this.data.scale = !0),
                  (this.data.hideUi = !0),
                  (this.data.scaleSize = this._getScaleBoarder()))
            },
          },
          {
            key: 'onUpdated',
            value: function () {
              var e = this
              if (
                (this.data.appearEffect &&
                  this.data.appearEffect.once &&
                  ((this.data.appearEffect.once = !1),
                  this.execEffect().then(function () {
                    ;(e.data.appearEffect = null),
                      (e.data.appear = !0),
                      e.update()
                  })),
                this.clicked)
              ) {
                this.clicked = !1
                var t = this.data.classNames,
                  r = this._getElementByClass(t.smartPhotoCaption)
                r.focus()
              }
            },
          },
          {
            key: 'execEffect',
            value: function () {
              var e = this
              return new d(function (t) {
                y.isOldIE() && t()
                var r = e.data,
                  a = r.appearEffect,
                  i = r.classNames,
                  c = e._getElementByClass(i.smartPhotoImgClone),
                  v = function E() {
                    c.removeEventListener('transitionend', E, !0), t()
                  }
                c.addEventListener('transitionend', v, !0),
                  setTimeout(function () {
                    c.style.transform = 'translate('
                      .concat(a.afterX, 'px, ')
                      .concat(a.afterY, 'px) scale(')
                      .concat(a.scale, ')')
                  }, 10)
              })
            },
          },
          {
            key: 'addAppearEffect',
            value: function (e, t) {
              if (this.data.showAnimation === !1) {
                this.data.appear = !0
                return
              }
              var r = e.querySelector('img'),
                a = y.getViewPos(r),
                i = {},
                c = 1
              ;(i.width = r.offsetWidth),
                (i.height = r.offsetHeight),
                (i.top = a.top),
                (i.left = a.left),
                (i.once = !0),
                r.getAttribute(this.data.lazyAttribute)
                  ? (i.img = r.getAttribute(this.data.lazyAttribute))
                  : (i.img = t.src)
              var v = this._getWindowWidth(),
                E = this._getWindowHeight(),
                I = E - this.data.headerHeight - this.data.footerHeight
              this.data.resizeStyle === 'fill' && this.data.isSmartPhone
                ? r.offsetWidth > r.offsetHeight
                  ? (c = E / r.offsetHeight)
                  : (c = v / r.offsetWidth)
                : (i.width >= i.height
                    ? t.height < I
                      ? (c = t.width / i.width)
                      : (c = I / i.height)
                    : i.height > i.width &&
                      (t.height < I
                        ? (c = t.height / i.height)
                        : (c = I / i.height)),
                  i.width * c > v && (c = v / i.width))
              var x =
                  ((c - 1) / 2) * r.offsetWidth + (v - r.offsetWidth * c) / 2,
                w =
                  ((c - 1) / 2) * r.offsetHeight + (E - r.offsetHeight * c) / 2
              ;(i.afterX = x),
                (i.afterY = w),
                (i.scale = c),
                (this.data.appearEffect = i)
            },
          },
          {
            key: 'hidePhoto',
            value: function () {
              var e = this,
                t =
                  arguments.length > 0 && arguments[0] !== void 0
                    ? arguments[0]
                    : 'bottom'
              ;(this.data.hide = !0),
                (this.data.appear = !1),
                (this.data.appearEffect = null),
                (this.data.hideUi = !1),
                (this.data.scale = !1),
                (this.data.scaleSize = 1)
              var r =
                  window.pageXOffset !== void 0
                    ? window.pageXOffset
                    : (
                        document.documentElement ||
                        document.body.parentNode ||
                        document.body
                      ).scrollLeft,
                a =
                  window.pageYOffset !== void 0
                    ? window.pageYOffset
                    : (
                        document.documentElement ||
                        document.body.parentNode ||
                        document.body
                      ).scrollTop,
                i = document.querySelector('body')
              window.location.hash && this._setHash(''),
                window.scroll(r, a),
                this._doHideEffect(t).then(function () {
                  e.update(), (i.style.overflow = ''), e._fireEvent('close')
                })
            },
          },
          {
            key: '_doHideEffect',
            value: function (e) {
              var t = this
              return new d(function (r) {
                y.isOldIE() && r()
                var a = t.data.classNames,
                  i = t._getElementByClass(a.smartPhoto),
                  c = t._getElementByQuery(
                    '.current .'.concat(a.smartPhotoImg),
                  ),
                  v = t._getWindowHeight(),
                  E = function I() {
                    i.removeEventListener('transitionend', I, !0), r()
                  }
                ;(i.style.opacity = 0),
                  e === 'bottom'
                    ? (c.style.transform = 'translateY('.concat(v, 'px)'))
                    : e === 'top' &&
                      (c.style.transform = 'translateY(-'.concat(v, 'px)')),
                  i.addEventListener('transitionend', E, !0)
              })
            },
          },
          {
            key: '_getElementByClass',
            value: function (e) {
              return document.querySelector(
                '[data-id="'.concat(this.id, '"] .').concat(e),
              )
            },
          },
          {
            key: '_getElementByQuery',
            value: function (e) {
              return document.querySelector(
                '[data-id="'.concat(this.id, '"] ').concat(e),
              )
            },
          },
          {
            key: '_getTouchPos',
            value: function () {
              var e = 0,
                t = 0,
                r = typeof event > 'u' ? this.e : event
              return (
                this._isTouched(r)
                  ? ((e = r.touches[0].pageX), (t = r.touches[0].pageY))
                  : r.pageX && ((e = r.pageX), (t = r.pageY)),
                { x: e, y: t }
              )
            },
          },
          {
            key: '_getGesturePos',
            value: function (e) {
              var t = e.touches
              return [
                { x: t[0].pageX, y: t[0].pageY },
                { x: t[1].pageX, y: t[1].pageY },
              ]
            },
          },
          {
            key: '_setPosByCurrentIndex',
            value: function () {
              var e = this,
                t = this.groupItems(),
                r = -1 * t[this.data.currentIndex].translateX
              ;(this.pos.x = r),
                setTimeout(function () {
                  ;(e.data.translateX = r),
                    (e.data.translateY = 0),
                    e._listUpdate()
                }, 1)
            },
          },
          {
            key: '_setHashByCurrentIndex',
            value: function () {
              var e =
                  window.pageXOffset !== void 0
                    ? window.pageXOffset
                    : (
                        document.documentElement ||
                        document.body.parentNode ||
                        document.body
                      ).scrollLeft,
                t =
                  window.pageYOffset !== void 0
                    ? window.pageYOffset
                    : (
                        document.documentElement ||
                        document.body.parentNode ||
                        document.body
                      ).scrollTop,
                r = this.groupItems(),
                a = r[this.data.currentIndex].id,
                i = this.data.currentGroup,
                c = 'group='.concat(i, '&photo=').concat(a)
              this._setHash(c), window.scroll(e, t)
            },
          },
          {
            key: '_setHash',
            value: function (e) {
              !(window.history && window.history.pushState) ||
                !this.data.useHistoryApi ||
                (e
                  ? window.history.replaceState(
                      null,
                      null,
                      ''
                        .concat(location.pathname)
                        .concat(location.search, '#')
                        .concat(e),
                    )
                  : window.history.replaceState(
                      null,
                      null,
                      ''.concat(location.pathname).concat(location.search),
                    ))
            },
          },
          {
            key: '_getCurrentItemByHash',
            value: function () {
              var e = this.data.group,
                t = location.hash.substr(1),
                r = y.parseQuery(t),
                a = null,
                i = function (v) {
                  r.group === v.groupId && r.photo === v.id && (a = v)
                }
              return (
                Object.keys(e).forEach(function (c) {
                  e[c].forEach(i)
                }),
                a
              )
            },
          },
          {
            key: '_loadItem',
            value: function (e) {
              return new d(function (t) {
                var r = new Image()
                ;(r.onload = function () {
                  ;(e.width = r.width),
                    (e.height = r.height),
                    (e.loaded = !0),
                    t()
                }),
                  (r.onerror = function () {
                    t()
                  }),
                  (r.src = e.src)
              })
            },
          },
          {
            key: '_getItemByIndex',
            value: function (e) {
              var t = this.data
              return t.group[t.currentGroup][e]
                ? t.group[t.currentGroup][e]
                : null
            },
          },
          {
            key: '_loadNeighborItems',
            value: function () {
              for (
                var e = this,
                  t = this.data.currentIndex,
                  r = this.data.loadOffset,
                  a = t - r,
                  i = t + r,
                  c = [],
                  v = a;
                v < i;
                v++
              ) {
                var E = this._getItemByIndex(v)
                E && !E.loaded && c.push(this._loadItem(E))
              }
              c.length &&
                d.all(c).then(function () {
                  e._initPhoto(), e.update()
                })
            },
          },
          {
            key: '_setSizeByScreen',
            value: function () {
              var e = this._getWindowWidth(),
                t = this._getWindowHeight(),
                r = this.data.headerHeight,
                a = this.data.footerHeight,
                i = t - (r + a),
                c = this.groupItems()
              c.forEach(function (v) {
                v.loaded &&
                  ((v.processed = !0),
                  (v.scale = i / v.height),
                  v.height < i && (v.scale = 1),
                  (v.x =
                    ((v.scale - 1) / 2) * v.width +
                    (e - v.width * v.scale) / 2),
                  (v.y =
                    ((v.scale - 1) / 2) * v.height +
                    (t - v.height * v.scale) / 2),
                  v.width * v.scale > e &&
                    ((v.scale = e / v.width),
                    (v.x = ((v.scale - 1) / 2) * v.width)))
              })
            },
          },
          {
            key: '_slideList',
            value: function () {
              var e = this
              ;(this.data.scaleSize = 1),
                (this.isBeingZoomed = !1),
                (this.data.hideUi = !1),
                (this.data.scale = !1),
                (this.data.photoPosX = 0),
                (this.data.photoPosY = 0),
                (this.data.onMoveClass = !0),
                this._setPosByCurrentIndex(),
                this._setHashByCurrentIndex(),
                this._setSizeByScreen(),
                setTimeout(function () {
                  var t = e._getSelectedItem()
                  ;(e.data.onMoveClass = !1),
                    e.setArrow(),
                    e.update(),
                    e.data.oldIndex !== e.data.currentIndex &&
                      e._fireEvent('change'),
                    (e.data.oldIndex = e.data.currentIndex),
                    e._loadNeighborItems(),
                    t.loaded ||
                      e._loadItem(t).then(function () {
                        e._initPhoto(), e.update()
                      })
                }, 200)
            },
          },
          {
            key: 'gotoSlide',
            value: function (e) {
              this.e && this.e.preventDefault && this.e.preventDefault(),
                (this.data.currentIndex = parseInt(e, 10)),
                this.data.currentIndex || (this.data.currentIndex = 0),
                this._slideList()
            },
          },
          {
            key: 'setArrow',
            value: function () {
              var e = this.groupItems(),
                t = e.length,
                r = this.data.currentIndex + 1,
                a = this.data.currentIndex - 1
              ;(this.data.showNextArrow = !1),
                (this.data.showPrevArrow = !1),
                r !== t &&
                  ((this.data.next = r), (this.data.showNextArrow = !0)),
                a !== -1 &&
                  ((this.data.prev = a), (this.data.showPrevArrow = !0))
            },
          },
          {
            key: 'beforeDrag',
            value: function () {
              if (this._isGestured(this.e)) {
                this.beforeGesture()
                return
              }
              if (((this.isBeingZoomed = !1), this.data.scale)) {
                this.beforePhotoDrag()
                return
              }
              var e = this._getTouchPos()
              ;(this.isSwipable = !0),
                (this.dragStart = !0),
                (this.firstPos = e),
                (this.oldPos = e)
            },
          },
          {
            key: 'afterDrag',
            value: function () {
              var e = this.groupItems(),
                t = new Date(),
                r = t.getTime(),
                a = this.tapSecond - r,
                i = 0,
                c = 0
              if (
                ((this.isSwipable = !1),
                (this.onListMove = !1),
                this.oldPos &&
                  ((i = this.oldPos.x - this.firstPos.x),
                  (c = this.oldPos.y - this.firstPos.y)),
                this.isBeingZoomed)
              ) {
                this.afterGesture()
                return
              }
              if (this.data.scale) {
                this.afterPhotoDrag()
                return
              } else if (!y.isSmartPhone() && i === 0 && c === 0) {
                this.zoomPhoto()
                return
              }
              if (Math.abs(a) <= 500 && i === 0 && c === 0) {
                this.e.preventDefault(), this.zoomPhoto()
                return
              }
              ;(this.tapSecond = r),
                this._fireEvent('swipeend'),
                this.moveDir === 'horizontal' &&
                  (i >= this.data.swipeOffset && this.data.currentIndex !== 0
                    ? (this.data.currentIndex -= 1)
                    : i <= -this.data.swipeOffset &&
                      this.data.currentIndex !== e.length - 1 &&
                      (this.data.currentIndex += 1),
                  this._slideList()),
                this.moveDir === 'vertical' &&
                  (this.data.swipeBottomToClose && c >= this.data.swipeOffset
                    ? this.hidePhoto('bottom')
                    : this.data.swipeTopToClose && c <= -this.data.swipeOffset
                    ? this.hidePhoto('top')
                    : ((this.data.translateY = 0), this._slideList()))
            },
          },
          {
            key: 'onDrag',
            value: function () {
              if (
                (this.e.preventDefault(),
                this._isGestured(this.e) && this.onListMove === !1)
              ) {
                this.onGesture()
                return
              }
              if (!this.isBeingZoomed) {
                if (this.data.scale) {
                  this.onPhotoDrag()
                  return
                }
                if (this.isSwipable) {
                  var e = this._getTouchPos(),
                    t = e.x - this.oldPos.x,
                    r = e.y - this.firstPos.y
                  this.dragStart &&
                    (this._fireEvent('swipestart'),
                    (this.dragStart = !1),
                    Math.abs(t) > Math.abs(r)
                      ? (this.moveDir = 'horizontal')
                      : (this.moveDir = 'vertical')),
                    this.moveDir === 'horizontal'
                      ? ((this.pos.x += t), (this.data.translateX = this.pos.x))
                      : (this.data.translateY = r),
                    (this.onListMove = !0),
                    (this.oldPos = e),
                    this._listUpdate()
                }
              }
            },
          },
          {
            key: 'zoomPhoto',
            value: function () {
              var e = this
              ;(this.data.hideUi = !0),
                (this.data.scaleSize = this._getScaleBoarder()),
                !(this.data.scaleSize <= 1) &&
                  ((this.data.photoPosX = 0),
                  (this.data.photoPosY = 0),
                  this._photoUpdate(),
                  setTimeout(function () {
                    ;(e.data.scale = !0),
                      e._photoUpdate(),
                      e._fireEvent('zoomin')
                  }, 300))
            },
          },
          {
            key: 'zoomOutPhoto',
            value: function () {
              ;(this.data.scaleSize = 1),
                (this.isBeingZoomed = !1),
                (this.data.hideUi = !1),
                (this.data.scale = !1),
                (this.data.photoPosX = 0),
                (this.data.photoPosY = 0),
                this._photoUpdate(),
                this._fireEvent('zoomout')
            },
          },
          {
            key: 'beforePhotoDrag',
            value: function () {
              var e = this._getTouchPos()
              ;(this.photoSwipable = !0),
                this.data.photoPosX || (this.data.photoPosX = 0),
                this.data.photoPosY || (this.data.photoPosY = 0),
                (this.oldPhotoPos = e),
                (this.firstPhotoPos = e)
            },
          },
          {
            key: 'onPhotoDrag',
            value: function () {
              if (this.photoSwipable) {
                this.e.preventDefault()
                var e = this._getTouchPos(),
                  t = e.x - this.oldPhotoPos.x,
                  r = e.y - this.oldPhotoPos.y,
                  a = this._round(this.data.scaleSize * t, 6),
                  i = this._round(this.data.scaleSize * r, 6)
                typeof a == 'number' &&
                  ((this.data.photoPosX += a), (this.photoVX = a)),
                  typeof i == 'number' &&
                    ((this.data.photoPosY += i), (this.photoVY = i)),
                  (this.oldPhotoPos = e),
                  this._photoUpdate()
              }
            },
          },
          {
            key: 'afterPhotoDrag',
            value: function () {
              if (
                this.oldPhotoPos.x === this.firstPhotoPos.x &&
                this.photoSwipable
              )
                (this.photoSwipable = !1), this.zoomOutPhoto()
              else {
                this.photoSwipable = !1
                var e = this._getSelectedItem(),
                  t = this._makeBound(e),
                  r = this.data.swipeOffset * this.data.scaleSize,
                  a = 0,
                  i = 0
                if (
                  (this.data.photoPosX > t.maxX
                    ? (a = -1)
                    : this.data.photoPosX < t.minX && (a = 1),
                  this.data.photoPosY > t.maxY
                    ? (i = -1)
                    : this.data.photoPosY < t.minY && (i = 1),
                  this.data.photoPosX - t.maxX > r &&
                    this.data.currentIndex !== 0)
                ) {
                  this.gotoSlide(this.data.prev)
                  return
                }
                if (
                  t.minX - this.data.photoPosX > r &&
                  this.data.currentIndex + 1 !== this.data.total
                ) {
                  this.gotoSlide(this.data.next)
                  return
                }
                a === 0 && i === 0
                  ? ((this.vx = this.photoVX / 5), (this.vy = this.photoVY / 5))
                  : this._registerElasticForce(a, i)
              }
            },
          },
          {
            key: 'beforeGesture',
            value: function () {
              this._fireEvent('gesturestart')
              var e = this._getGesturePos(this.e),
                t = this._getDistance(e[0], e[1])
              ;(this.isBeingZoomed = !0),
                (this.oldDistance = t),
                (this.data.scale = !0),
                this.e.preventDefault()
            },
          },
          {
            key: 'onGesture',
            value: function () {
              var e = this._getGesturePos(this.e),
                t = this._getDistance(e[0], e[1]),
                r = (t - this.oldDistance) / 100,
                a = this.data.scaleSize,
                i = this.data.photoPosX,
                c = this.data.photoPosY
              ;(this.isBeingZoomed = !0),
                (this.data.scaleSize += this._round(r, 6)),
                this.data.scaleSize < 0.2 && (this.data.scaleSize = 0.2),
                this.data.scaleSize < a &&
                  ((this.data.photoPosX = (1 + this.data.scaleSize - a) * i),
                  (this.data.photoPosY = (1 + this.data.scaleSize - a) * c)),
                this.data.scaleSize < 1 ||
                this.data.scaleSize > this._getScaleBoarder()
                  ? (this.data.hideUi = !0)
                  : (this.data.hideUi = !1),
                (this.oldDistance = t),
                this.e.preventDefault(),
                this._photoUpdate()
            },
          },
          {
            key: 'afterGesture',
            value: function () {
              this.data.scaleSize > this._getScaleBoarder() ||
                ((this.data.photoPosX = 0),
                (this.data.photoPosY = 0),
                (this.data.scale = !1),
                (this.data.scaleSize = 1),
                (this.data.hideUi = !1),
                this._fireEvent('gestureend'),
                this._photoUpdate())
            },
          },
          {
            key: '_getForceAndTheta',
            value: function (e, t) {
              return {
                force: Math.sqrt(e * e + t * t),
                theta: Math.atan2(t, e),
              }
            },
          },
          {
            key: '_getScaleBoarder',
            value: function () {
              var e = this._getSelectedItem(),
                t = this._getWindowWidth(),
                r = this._getWindowHeight()
              return y.isSmartPhone()
                ? e.width > e.height
                  ? r / (e.height * e.scale)
                  : t / (e.width * e.scale)
                : 1 / e.scale
            },
          },
          {
            key: '_makeBound',
            value: function (e) {
              var t = e.width * e.scale * this.data.scaleSize,
                r = e.height * e.scale * this.data.scaleSize,
                a,
                i,
                c,
                v,
                E = this._getWindowWidth(),
                I = this._getWindowHeight()
              return (
                E > t
                  ? ((c = (E - t) / 2), (a = -1 * c))
                  : ((c = (t - E) / 2), (a = -1 * c)),
                I > r
                  ? ((v = (I - r) / 2), (i = -1 * v))
                  : ((v = (r - I) / 2), (i = -1 * v)),
                {
                  minX: this._round(a, 6) * this.data.scaleSize,
                  minY: this._round(i, 6) * this.data.scaleSize,
                  maxX: this._round(c, 6) * this.data.scaleSize,
                  maxY: this._round(v, 6) * this.data.scaleSize,
                }
              )
            },
          },
          {
            key: '_registerElasticForce',
            value: function (e, t) {
              var r = this,
                a = this._getSelectedItem(),
                i = this._makeBound(a)
              ;(this.data.elastic = !0),
                e === 1
                  ? (this.data.photoPosX = i.minX)
                  : e === -1 && (this.data.photoPosX = i.maxX),
                t === 1
                  ? (this.data.photoPosY = i.minY)
                  : t === -1 && (this.data.photoPosY = i.maxY),
                this._photoUpdate(),
                setTimeout(function () {
                  ;(r.data.elastic = !1), r._photoUpdate()
                }, 300)
            },
          },
          {
            key: '_getSelectedItem',
            value: function () {
              var e = this.data,
                t = e.currentIndex
              return e.group[e.currentGroup][t]
            },
          },
          {
            key: '_getUniqId',
            value: function () {
              return (
                Date.now().toString(36) +
                Math.random().toString(36).substr(2, 5)
              ).toUpperCase()
            },
          },
          {
            key: '_getDistance',
            value: function (e, t) {
              var r = e.x - t.x,
                a = e.y - t.y
              return Math.sqrt(r * r + a * a)
            },
          },
          {
            key: '_round',
            value: function (e, t) {
              var r = Math.pow(10, t)
              return (e *= r), (e = Math.round(e)), (e /= r), e
            },
          },
          {
            key: '_isTouched',
            value: function (e) {
              return !!(e && e.touches)
            },
          },
          {
            key: '_isGestured',
            value: function (e) {
              return !!(e && e.touches && e.touches.length > 1)
            },
          },
          {
            key: '_isSmartPhone',
            value: function () {
              var e = navigator.userAgent
              return (
                e.indexOf('iPhone') > 0 ||
                e.indexOf('iPad') > 0 ||
                e.indexOf('ipod') > 0 ||
                e.indexOf('Android') > 0
              )
            },
          },
          {
            key: '_calcGravity',
            value: function (e, t) {
              ;(e > 5 || e < -5) && (this.vx += e * 0.05),
                this.data.verticalGravity !== !1 &&
                  (t > 5 || t < -5) &&
                  (this.vy += t * 0.05)
            },
          },
          {
            key: '_photoUpdate',
            value: function () {
              var e = this.data.classNames,
                t = this._getElementByQuery('.current'),
                r = t.querySelector('.'.concat(e.smartPhotoImg)),
                a = this._getElementByQuery('.'.concat(e.smartPhotoNav)),
                i = this._getElementByQuery('.'.concat(e.smartPhotoArrows)),
                c = this.virtualPos(this.data.photoPosX),
                v = this.virtualPos(this.data.photoPosY),
                E = this.data.scaleSize,
                I = 'translate('
                  .concat(c, 'px,')
                  .concat(v, 'px) scale(')
                  .concat(E, ')')
              ;(r.style.transform = I),
                this.data.scale
                  ? y.addClass(r, e.smartPhotoImgOnMove)
                  : y.removeClass(r, e.smartPhotoImgOnMove),
                this.data.elastic
                  ? y.addClass(r, e.smartPhotoImgElasticMove)
                  : y.removeClass(r, e.smartPhotoImgElasticMove),
                this.data.hideUi
                  ? (a && a.setAttribute('aria-hidden', 'true'),
                    i && i.setAttribute('aria-hidden', 'true'))
                  : (a && a.setAttribute('aria-hidden', 'false'),
                    i && i.setAttribute('aria-hidden', 'false'))
            },
          },
          {
            key: '_getWindowWidth',
            value: function () {
              return document && document.documentElement
                ? document.documentElement.clientWidth
                : window && window.innerWidth
                ? window.innerWidth
                : 0
            },
          },
          {
            key: '_getWindowHeight',
            value: function () {
              return document && document.documentElement
                ? document.documentElement.clientHeight
                : window && window.innerHeight
                ? window.innerHeight
                : 0
            },
          },
          {
            key: '_listUpdate',
            value: function () {
              var e = this.data.classNames,
                t = this._getElementByQuery('.'.concat(e.smartPhotoList)),
                r = 'translate('
                  .concat(this.data.translateX, 'px,')
                  .concat(this.data.translateY, 'px)')
              ;(t.style.transform = r),
                this.data.onMoveClass
                  ? y.addClass(t, e.smartPhotoListOnMove)
                  : y.removeClass(t, e.smartPhotoListOnMove)
            },
          },
          {
            key: '_fireEvent',
            value: function (e) {
              var t = this._getElementByClass(this.data.classNames.smartPhoto)
              y.triggerEvent(t, e)
            },
          },
          {
            key: '_doAnim',
            value: function () {
              if (
                !(
                  this.isBeingZoomed ||
                  this.isSwipable ||
                  this.photoSwipable ||
                  this.data.elastic ||
                  !this.data.scale
                )
              ) {
                ;(this.data.photoPosX += this.vx),
                  (this.data.photoPosY += this.vy)
                var e = this._getSelectedItem(),
                  t = this._makeBound(e)
                this.data.photoPosX < t.minX
                  ? ((this.data.photoPosX = t.minX), (this.vx *= -0.2))
                  : this.data.photoPosX > t.maxX &&
                    ((this.data.photoPosX = t.maxX), (this.vx *= -0.2)),
                  this.data.photoPosY < t.minY
                    ? ((this.data.photoPosY = t.minY), (this.vy *= -0.2))
                    : this.data.photoPosY > t.maxY &&
                      ((this.data.photoPosY = t.maxY), (this.vy *= -0.2))
                var r = this._getForceAndTheta(this.vx, this.vy),
                  a = r.force,
                  i = r.theta
                ;(a -= this.data.registance),
                  !(Math.abs(a) < 0.5) &&
                    ((this.vx = Math.cos(i) * a),
                    (this.vy = Math.sin(i) * a),
                    this._photoUpdate())
              }
            },
          },
        ]),
        _
      )
    })(n.default)
  ;(o.default = f), (u.exports = o.default)
})(it, it.exports)
var Ft = it.exports,
  Vt = Ft
const $t = gt(Vt),
  Zt = (u, o = {}) => {
    const n = {
      classNames: {
        smartPhoto: 'smartphoto',
        smartPhotoClose: 'smartphoto-close',
        smartPhotoBody: 'smartphoto-body',
        smartPhotoInner: 'smartphoto-inner',
        smartPhotoContent: 'smartphoto-content',
        smartPhotoImg: 'smartphoto-img',
        smartPhotoImgOnMove: 'smartphoto-img-onmove',
        smartPhotoImgElasticMove: 'smartphoto-img-elasticmove',
        smartPhotoImgWrap: 'smartphoto-img-wrap',
        smartPhotoArrows: 'smartphoto-arrows',
        smartPhotoNav: 'smartphoto-nav',
        smartPhotoArrowRight: 'smartphoto-arrow-right',
        smartPhotoArrowLeft: 'smartphoto-arrow-left',
        smartPhotoImgLeft: 'smartphoto-img-left',
        smartPhotoImgRight: 'smartphoto-img-right',
        smartPhotoList: 'smartphoto-list',
        smartPhotoListOnMove: 'smartphoto-list-onmove',
        smartPhotoHeader: 'smartphoto-header',
        smartPhotoCount: 'smartphoto-count',
        smartPhotoCaption: 'smartphoto-caption',
        smartPhotoDismiss: 'smartphoto-dismiss',
        smartPhotoLoader: 'smartphoto-loader',
        smartPhotoLoaderWrap: 'smartphoto-loader-wrap',
        smartPhotoImgClone: 'smartphoto-img-clone',
      },
      message: {
        gotoNextImage: '次の画像に移動します',
        gotoPrevImage: '前の画像に移動します',
        closeDialog: '画像ダイアログを閉じます',
      },
      arrows: !0,
      nav: !0,
      animationSpeed: 300,
      swipeOffset: 100,
      headerHeight: 60,
      footerHeight: 60,
      forceInterval: 10,
      registance: 0.5,
      resizeStyle: 'fit',
      verticalGravity: !1,
      useOrientationApi: !1,
      useHistoryApi: !0,
      lazyAttribute: 'data-src',
    }
    new $t(u, Object.assign(n, o))
  }
export { Zt as default }
