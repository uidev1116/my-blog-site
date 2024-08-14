import { r as u, W as T, j as S } from './admin-C8v7te02.js'
import './preload-helper-CqCcXmTM.js'
function we(e, t, r) {
  return (
    t in e
      ? Object.defineProperty(e, t, {
          value: r,
          enumerable: !0,
          configurable: !0,
          writable: !0,
        })
      : (e[t] = r),
    e
  )
}
function ae(e, t) {
  var r = Object.keys(e)
  if (Object.getOwnPropertySymbols) {
    var n = Object.getOwnPropertySymbols(e)
    t &&
      (n = n.filter(function (a) {
        return Object.getOwnPropertyDescriptor(e, a).enumerable
      })),
      r.push.apply(r, n)
  }
  return r
}
function ie(e) {
  for (var t = 1; t < arguments.length; t++) {
    var r = arguments[t] != null ? arguments[t] : {}
    t % 2
      ? ae(Object(r), !0).forEach(function (n) {
          we(e, n, r[n])
        })
      : Object.getOwnPropertyDescriptors
      ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(r))
      : ae(Object(r)).forEach(function (n) {
          Object.defineProperty(e, n, Object.getOwnPropertyDescriptor(r, n))
        })
  }
  return e
}
function je(e, t) {
  if (e == null) return {}
  var r = {},
    n = Object.keys(e),
    a,
    i
  for (i = 0; i < n.length; i++)
    (a = n[i]), !(t.indexOf(a) >= 0) && (r[a] = e[a])
  return r
}
function Oe(e, t) {
  if (e == null) return {}
  var r = je(e, t),
    n,
    a
  if (Object.getOwnPropertySymbols) {
    var i = Object.getOwnPropertySymbols(e)
    for (a = 0; a < i.length; a++)
      (n = i[a]),
        !(t.indexOf(n) >= 0) &&
          Object.prototype.propertyIsEnumerable.call(e, n) &&
          (r[n] = e[n])
  }
  return r
}
function Me(e, t) {
  return Se(e) || Pe(e, t) || Ee(e, t) || xe()
}
function Se(e) {
  if (Array.isArray(e)) return e
}
function Pe(e, t) {
  if (!(typeof Symbol > 'u' || !(Symbol.iterator in Object(e)))) {
    var r = [],
      n = !0,
      a = !1,
      i = void 0
    try {
      for (
        var l = e[Symbol.iterator](), s;
        !(n = (s = l.next()).done) && (r.push(s.value), !(t && r.length === t));
        n = !0
      );
    } catch (g) {
      ;(a = !0), (i = g)
    } finally {
      try {
        !n && l.return != null && l.return()
      } finally {
        if (a) throw i
      }
    }
    return r
  }
}
function Ee(e, t) {
  if (e) {
    if (typeof e == 'string') return oe(e, t)
    var r = Object.prototype.toString.call(e).slice(8, -1)
    if (
      (r === 'Object' && e.constructor && (r = e.constructor.name),
      r === 'Map' || r === 'Set')
    )
      return Array.from(e)
    if (r === 'Arguments' || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(r))
      return oe(e, t)
  }
}
function oe(e, t) {
  ;(t == null || t > e.length) && (t = e.length)
  for (var r = 0, n = new Array(t); r < t; r++) n[r] = e[r]
  return n
}
function xe() {
  throw new TypeError(`Invalid attempt to destructure non-iterable instance.
In order to be iterable, non-array objects must have a [Symbol.iterator]() method.`)
}
function Ce(e, t, r) {
  return (
    t in e
      ? Object.defineProperty(e, t, {
          value: r,
          enumerable: !0,
          configurable: !0,
          writable: !0,
        })
      : (e[t] = r),
    e
  )
}
function ue(e, t) {
  var r = Object.keys(e)
  if (Object.getOwnPropertySymbols) {
    var n = Object.getOwnPropertySymbols(e)
    t &&
      (n = n.filter(function (a) {
        return Object.getOwnPropertyDescriptor(e, a).enumerable
      })),
      r.push.apply(r, n)
  }
  return r
}
function ce(e) {
  for (var t = 1; t < arguments.length; t++) {
    var r = arguments[t] != null ? arguments[t] : {}
    t % 2
      ? ue(Object(r), !0).forEach(function (n) {
          Ce(e, n, r[n])
        })
      : Object.getOwnPropertyDescriptors
      ? Object.defineProperties(e, Object.getOwnPropertyDescriptors(r))
      : ue(Object(r)).forEach(function (n) {
          Object.defineProperty(e, n, Object.getOwnPropertyDescriptor(r, n))
        })
  }
  return e
}
function Re() {
  for (var e = arguments.length, t = new Array(e), r = 0; r < e; r++)
    t[r] = arguments[r]
  return function (n) {
    return t.reduceRight(function (a, i) {
      return i(a)
    }, n)
  }
}
function H(e) {
  return function t() {
    for (
      var r = this, n = arguments.length, a = new Array(n), i = 0;
      i < n;
      i++
    )
      a[i] = arguments[i]
    return a.length >= e.length
      ? e.apply(this, a)
      : function () {
          for (var l = arguments.length, s = new Array(l), g = 0; g < l; g++)
            s[g] = arguments[g]
          return t.apply(r, [].concat(a, s))
        }
  }
}
function _(e) {
  return {}.toString.call(e).includes('Object')
}
function Ie(e) {
  return !Object.keys(e).length
}
function z(e) {
  return typeof e == 'function'
}
function Le(e, t) {
  return Object.prototype.hasOwnProperty.call(e, t)
}
function $e(e, t) {
  return (
    _(t) || E('changeType'),
    Object.keys(t).some(function (r) {
      return !Le(e, r)
    }) && E('changeField'),
    t
  )
}
function Te(e) {
  z(e) || E('selectorType')
}
function Ae(e) {
  z(e) || _(e) || E('handlerType'),
    _(e) &&
      Object.values(e).some(function (t) {
        return !z(t)
      }) &&
      E('handlersType')
}
function Ve(e) {
  e || E('initialIsRequired'),
    _(e) || E('initialType'),
    Ie(e) && E('initialContent')
}
function De(e, t) {
  throw new Error(e[t] || e.default)
}
var qe = {
    initialIsRequired: 'initial state is required',
    initialType: 'initial state should be an object',
    initialContent: "initial state shouldn't be an empty object",
    handlerType: 'handler should be an object or a function',
    handlersType: 'all handlers should be a functions',
    selectorType: 'selector should be a function',
    changeType: 'provided value of changes should be an object',
    changeField:
      'it seams you want to change a field in the state which is not specified in the "initial" state',
    default: 'an unknown error accured in `state-local` package',
  },
  E = H(De)(qe),
  J = { changes: $e, selector: Te, handler: Ae, initial: Ve }
function Ne(e) {
  var t = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : {}
  J.initial(e), J.handler(t)
  var r = { current: e },
    n = H(ze)(r, t),
    a = H(Ue)(r),
    i = H(J.changes)(e),
    l = H(He)(r)
  function s() {
    var y =
      arguments.length > 0 && arguments[0] !== void 0
        ? arguments[0]
        : function (C) {
            return C
          }
    return J.selector(y), y(r.current)
  }
  function g(y) {
    Re(n, a, i, l)(y)
  }
  return [s, g]
}
function He(e, t) {
  return z(t) ? t(e.current) : t
}
function Ue(e, t) {
  return (e.current = ce(ce({}, e.current), t)), t
}
function ze(e, t, r) {
  return (
    z(t)
      ? t(e.current)
      : Object.keys(r).forEach(function (n) {
          var a
          return (a = t[n]) === null || a === void 0
            ? void 0
            : a.call(t, e.current[n])
        }),
    r
  )
}
var ke = { create: Ne },
  Fe = {
    paths: { vs: 'https://cdn.jsdelivr.net/npm/monaco-editor@0.43.0/min/vs' },
  }
function We(e) {
  return function t() {
    for (
      var r = this, n = arguments.length, a = new Array(n), i = 0;
      i < n;
      i++
    )
      a[i] = arguments[i]
    return a.length >= e.length
      ? e.apply(this, a)
      : function () {
          for (var l = arguments.length, s = new Array(l), g = 0; g < l; g++)
            s[g] = arguments[g]
          return t.apply(r, [].concat(a, s))
        }
  }
}
function Je(e) {
  return {}.toString.call(e).includes('Object')
}
function Xe(e) {
  return (
    e || le('configIsRequired'),
    Je(e) || le('configType'),
    e.urls ? (_e(), { paths: { vs: e.urls.monacoBase } }) : e
  )
}
function _e() {
  console.warn(fe.deprecation)
}
function Be(e, t) {
  throw new Error(e[t] || e.default)
}
var fe = {
    configIsRequired: 'the configuration object is required',
    configType: 'the configuration object should be an object',
    default: 'an unknown error accured in `@monaco-editor/loader` package',
    deprecation: `Deprecation warning!
    You are using deprecated way of configuration.

    Instead of using
      monaco.config({ urls: { monacoBase: '...' } })
    use
      monaco.config({ paths: { vs: '...' } })

    For more please check the link https://github.com/suren-atoyan/monaco-loader#config
  `,
  },
  le = We(Be)(fe),
  Ye = { config: Xe },
  Ge = function () {
    for (var t = arguments.length, r = new Array(t), n = 0; n < t; n++)
      r[n] = arguments[n]
    return function (a) {
      return r.reduceRight(function (i, l) {
        return l(i)
      }, a)
    }
  }
function de(e, t) {
  return (
    Object.keys(t).forEach(function (r) {
      t[r] instanceof Object && e[r] && Object.assign(t[r], de(e[r], t[r]))
    }),
    ie(ie({}, e), t)
  )
}
var Ke = { type: 'cancelation', msg: 'operation is manually canceled' }
function te(e) {
  var t = !1,
    r = new Promise(function (n, a) {
      e.then(function (i) {
        return t ? a(Ke) : n(i)
      }),
        e.catch(a)
    })
  return (
    (r.cancel = function () {
      return (t = !0)
    }),
    r
  )
}
var Qe = ke.create({
    config: Fe,
    isInitialized: !1,
    resolve: null,
    reject: null,
    monaco: null,
  }),
  pe = Me(Qe, 2),
  k = pe[0],
  B = pe[1]
function Ze(e) {
  var t = Ye.config(e),
    r = t.monaco,
    n = Oe(t, ['monaco'])
  B(function (a) {
    return { config: de(a.config, n), monaco: r }
  })
}
function et() {
  var e = k(function (t) {
    var r = t.monaco,
      n = t.isInitialized,
      a = t.resolve
    return { monaco: r, isInitialized: n, resolve: a }
  })
  if (!e.isInitialized) {
    if ((B({ isInitialized: !0 }), e.monaco)) return e.resolve(e.monaco), te(re)
    if (window.monaco && window.monaco.editor)
      return ge(window.monaco), e.resolve(window.monaco), te(re)
    Ge(tt, nt)(at)
  }
  return te(re)
}
function tt(e) {
  return document.body.appendChild(e)
}
function rt(e) {
  var t = document.createElement('script')
  return e && (t.src = e), t
}
function nt(e) {
  var t = k(function (n) {
      var a = n.config,
        i = n.reject
      return { config: a, reject: i }
    }),
    r = rt(''.concat(t.config.paths.vs, '/loader.js'))
  return (
    (r.onload = function () {
      return e()
    }),
    (r.onerror = t.reject),
    r
  )
}
function at() {
  var e = k(function (r) {
      var n = r.config,
        a = r.resolve,
        i = r.reject
      return { config: n, resolve: a, reject: i }
    }),
    t = window.require
  t.config(e.config),
    t(
      ['vs/editor/editor.main'],
      function (r) {
        ge(r), e.resolve(r)
      },
      function (r) {
        e.reject(r)
      },
    )
}
function ge(e) {
  k().monaco || B({ monaco: e })
}
function it() {
  return k(function (e) {
    var t = e.monaco
    return t
  })
}
var re = new Promise(function (e, t) {
    return B({ resolve: e, reject: t })
  }),
  he = { config: Ze, init: et, __getMonacoInstance: it },
  ot = {
    wrapper: { display: 'flex', position: 'relative', textAlign: 'initial' },
    fullWidth: { width: '100%' },
    hide: { display: 'none' },
  },
  ne = ot,
  ut = {
    container: {
      display: 'flex',
      height: '100%',
      width: '100%',
      justifyContent: 'center',
      alignItems: 'center',
    },
  },
  ct = ut
function lt({ children: e }) {
  return T.createElement('div', { style: ct.container }, e)
}
var st = lt,
  ft = st
function dt({
  width: e,
  height: t,
  isEditorReady: r,
  loading: n,
  _ref: a,
  className: i,
  wrapperProps: l,
}) {
  return T.createElement(
    'section',
    { style: { ...ne.wrapper, width: e, height: t }, ...l },
    !r && T.createElement(ft, null, n),
    T.createElement('div', {
      ref: a,
      style: { ...ne.fullWidth, ...(!r && ne.hide) },
      className: i,
    }),
  )
}
var pt = dt,
  ve = u.memo(pt)
function gt(e) {
  u.useEffect(e, [])
}
var me = gt
function ht(e, t, r = !0) {
  let n = u.useRef(!0)
  u.useEffect(
    n.current || !r
      ? () => {
          n.current = !1
        }
      : e,
    t,
  )
}
var O = ht
function U() {}
function $(e, t, r, n) {
  return vt(e, n) || mt(e, t, r, n)
}
function vt(e, t) {
  return e.editor.getModel(be(e, t))
}
function mt(e, t, r, n) {
  return e.editor.createModel(t, r, n ? be(e, n) : void 0)
}
function be(e, t) {
  return e.Uri.parse(t)
}
function bt({
  original: e,
  modified: t,
  language: r,
  originalLanguage: n,
  modifiedLanguage: a,
  originalModelPath: i,
  modifiedModelPath: l,
  keepCurrentOriginalModel: s = !1,
  keepCurrentModifiedModel: g = !1,
  theme: y = 'light',
  loading: C = 'Loading...',
  options: M = {},
  height: Y = '100%',
  width: G = '100%',
  className: K,
  wrapperProps: Q = {},
  beforeMount: Z = U,
  onMount: ee = U,
}) {
  let [w, A] = u.useState(!1),
    [x, v] = u.useState(!0),
    m = u.useRef(null),
    h = u.useRef(null),
    V = u.useRef(null),
    b = u.useRef(ee),
    f = u.useRef(Z),
    R = u.useRef(!1)
  me(() => {
    let o = he.init()
    return (
      o
        .then((d) => (h.current = d) && v(!1))
        .catch(
          (d) =>
            (d == null ? void 0 : d.type) !== 'cancelation' &&
            console.error('Monaco initialization: error:', d),
        ),
      () => (m.current ? D() : o.cancel())
    )
  }),
    O(
      () => {
        if (m.current && h.current) {
          let o = m.current.getOriginalEditor(),
            d = $(h.current, e || '', n || r || 'text', i || '')
          d !== o.getModel() && o.setModel(d)
        }
      },
      [i],
      w,
    ),
    O(
      () => {
        if (m.current && h.current) {
          let o = m.current.getModifiedEditor(),
            d = $(h.current, t || '', a || r || 'text', l || '')
          d !== o.getModel() && o.setModel(d)
        }
      },
      [l],
      w,
    ),
    O(
      () => {
        let o = m.current.getModifiedEditor()
        o.getOption(h.current.editor.EditorOption.readOnly)
          ? o.setValue(t || '')
          : t !== o.getValue() &&
            (o.executeEdits('', [
              {
                range: o.getModel().getFullModelRange(),
                text: t || '',
                forceMoveMarkers: !0,
              },
            ]),
            o.pushUndoStop())
      },
      [t],
      w,
    ),
    O(
      () => {
        var o, d
        ;(d = (o = m.current) == null ? void 0 : o.getModel()) == null ||
          d.original.setValue(e || '')
      },
      [e],
      w,
    ),
    O(
      () => {
        let { original: o, modified: d } = m.current.getModel()
        h.current.editor.setModelLanguage(o, n || r || 'text'),
          h.current.editor.setModelLanguage(d, a || r || 'text')
      },
      [r, n, a],
      w,
    ),
    O(
      () => {
        var o
        ;(o = h.current) == null || o.editor.setTheme(y)
      },
      [y],
      w,
    ),
    O(
      () => {
        var o
        ;(o = m.current) == null || o.updateOptions(M)
      },
      [M],
      w,
    )
  let F = u.useCallback(() => {
      var P
      if (!h.current) return
      f.current(h.current)
      let o = $(h.current, e || '', n || r || 'text', i || ''),
        d = $(h.current, t || '', a || r || 'text', l || '')
      ;(P = m.current) == null || P.setModel({ original: o, modified: d })
    }, [r, t, a, e, n, i, l]),
    W = u.useCallback(() => {
      var o
      !R.current &&
        V.current &&
        ((m.current = h.current.editor.createDiffEditor(V.current, {
          automaticLayout: !0,
          ...M,
        })),
        F(),
        (o = h.current) == null || o.editor.setTheme(y),
        A(!0),
        (R.current = !0))
    }, [M, y, F])
  u.useEffect(() => {
    w && b.current(m.current, h.current)
  }, [w]),
    u.useEffect(() => {
      !x && !w && W()
    }, [x, w, W])
  function D() {
    var d, P, I, q
    let o = (d = m.current) == null ? void 0 : d.getModel()
    s || (P = o == null ? void 0 : o.original) == null || P.dispose(),
      g || (I = o == null ? void 0 : o.modified) == null || I.dispose(),
      (q = m.current) == null || q.dispose()
  }
  return T.createElement(ve, {
    width: G,
    height: Y,
    isEditorReady: w,
    loading: C,
    _ref: V,
    className: K,
    wrapperProps: Q,
  })
}
var yt = bt
u.memo(yt)
function wt(e) {
  let t = u.useRef()
  return (
    u.useEffect(() => {
      t.current = e
    }, [e]),
    t.current
  )
}
var jt = wt,
  X = new Map()
function Ot({
  defaultValue: e,
  defaultLanguage: t,
  defaultPath: r,
  value: n,
  language: a,
  path: i,
  theme: l = 'light',
  line: s,
  loading: g = 'Loading...',
  options: y = {},
  overrideServices: C = {},
  saveViewState: M = !0,
  keepCurrentModel: Y = !1,
  width: G = '100%',
  height: K = '100%',
  className: Q,
  wrapperProps: Z = {},
  beforeMount: ee = U,
  onMount: w = U,
  onChange: A,
  onValidate: x = U,
}) {
  let [v, m] = u.useState(!1),
    [h, V] = u.useState(!0),
    b = u.useRef(null),
    f = u.useRef(null),
    R = u.useRef(null),
    F = u.useRef(w),
    W = u.useRef(ee),
    D = u.useRef(),
    o = u.useRef(n),
    d = jt(i),
    P = u.useRef(!1),
    I = u.useRef(!1)
  me(() => {
    let c = he.init()
    return (
      c
        .then((p) => (b.current = p) && V(!1))
        .catch(
          (p) =>
            (p == null ? void 0 : p.type) !== 'cancelation' &&
            console.error('Monaco initialization: error:', p),
        ),
      () => (f.current ? ye() : c.cancel())
    )
  }),
    O(
      () => {
        var p, j, N, L
        let c = $(b.current, e || n || '', t || a || '', i || r || '')
        c !== ((p = f.current) == null ? void 0 : p.getModel()) &&
          (M && X.set(d, (j = f.current) == null ? void 0 : j.saveViewState()),
          (N = f.current) == null || N.setModel(c),
          M && ((L = f.current) == null || L.restoreViewState(X.get(i))))
      },
      [i],
      v,
    ),
    O(
      () => {
        var c
        ;(c = f.current) == null || c.updateOptions(y)
      },
      [y],
      v,
    ),
    O(
      () => {
        !f.current ||
          n === void 0 ||
          (f.current.getOption(b.current.editor.EditorOption.readOnly)
            ? f.current.setValue(n)
            : n !== f.current.getValue() &&
              ((I.current = !0),
              f.current.executeEdits('', [
                {
                  range: f.current.getModel().getFullModelRange(),
                  text: n,
                  forceMoveMarkers: !0,
                },
              ]),
              f.current.pushUndoStop(),
              (I.current = !1)))
      },
      [n],
      v,
    ),
    O(
      () => {
        var p, j
        let c = (p = f.current) == null ? void 0 : p.getModel()
        c && a && ((j = b.current) == null || j.editor.setModelLanguage(c, a))
      },
      [a],
      v,
    ),
    O(
      () => {
        var c
        s !== void 0 && ((c = f.current) == null || c.revealLine(s))
      },
      [s],
      v,
    ),
    O(
      () => {
        var c
        ;(c = b.current) == null || c.editor.setTheme(l)
      },
      [l],
      v,
    )
  let q = u.useCallback(() => {
    var c
    if (!(!R.current || !b.current) && !P.current) {
      W.current(b.current)
      let p = i || r,
        j = $(b.current, n || e || '', t || a || '', p || '')
      ;(f.current =
        (c = b.current) == null
          ? void 0
          : c.editor.create(
              R.current,
              { model: j, automaticLayout: !0, ...y },
              C,
            )),
        M && f.current.restoreViewState(X.get(p)),
        b.current.editor.setTheme(l),
        s !== void 0 && f.current.revealLine(s),
        m(!0),
        (P.current = !0)
    }
  }, [e, t, r, n, a, i, y, C, M, l, s])
  u.useEffect(() => {
    v && F.current(f.current, b.current)
  }, [v]),
    u.useEffect(() => {
      !h && !v && q()
    }, [h, v, q]),
    (o.current = n),
    u.useEffect(() => {
      var c, p
      v &&
        A &&
        ((c = D.current) == null || c.dispose(),
        (D.current =
          (p = f.current) == null
            ? void 0
            : p.onDidChangeModelContent((j) => {
                I.current || A(f.current.getValue(), j)
              })))
    }, [v, A]),
    u.useEffect(() => {
      if (v) {
        let c = b.current.editor.onDidChangeMarkers((p) => {
          var N
          let j = (N = f.current.getModel()) == null ? void 0 : N.uri
          if (j && p.find((L) => L.path === j.path)) {
            let L = b.current.editor.getModelMarkers({ resource: j })
            x == null || x(L)
          }
        })
        return () => {
          c == null || c.dispose()
        }
      }
      return () => {}
    }, [v, x])
  function ye() {
    var c, p
    ;(c = D.current) == null || c.dispose(),
      Y
        ? M && X.set(i, f.current.saveViewState())
        : (p = f.current.getModel()) == null || p.dispose(),
      f.current.dispose()
  }
  return T.createElement(ve, {
    width: G,
    height: K,
    isEditorReady: v,
    loading: g,
    _ref: R,
    className: Q,
    wrapperProps: Z,
  })
}
var Mt = Ot,
  St = u.memo(Mt),
  Pt = St
const se = new Map([
    ['typescript', 'typescript'],
    ['javascript', 'javascript'],
    ['json', 'json'],
    ['markdown', 'markdown'],
    ['shell', 'shell'],
    ['yaml', 'yaml'],
    ['css', 'css'],
    ['html', 'html'],
    ['xml', 'xml'],
    ['csv', 'csv'],
    ['jsx', 'javascript'],
    ['tsx', 'typescript'],
    ['php', 'php'],
    ['sass', 'scss'],
    ['scss', 'scss'],
    ['vue', 'javascript'],
    ['sql', 'sql'],
    ['postcss', 'css'],
    ['text', 'plaintext'],
  ]),
  Et = [
    { value: 'text', label: 'Plain Text' },
    { value: 'html', label: 'HTML' },
    { value: 'css', label: 'CSS' },
    { value: 'sass', label: 'Sass' },
    { value: 'scss', label: 'SCSS' },
    { value: 'postcss', label: 'PostCSS' },
    { value: 'javascript', label: 'JavaScript' },
    { value: 'jsx', label: 'JSX' },
    { value: 'typescript', label: 'TypeScript' },
    { value: 'tsx', label: 'TSX' },
    { value: 'vue', label: 'Vue' },
    { value: 'php', label: 'PHP' },
    { value: 'json', label: 'JSON' },
    { value: 'shell', label: 'Shell' },
    { value: 'yaml', label: 'YAML' },
    { value: 'xml', label: 'XML' },
    { value: 'csv', label: 'CSV' },
    { value: 'markdown', label: 'Markdown' },
    { value: 'sql', label: 'SQL' },
  ]
function Rt({ id: e, defaultCode: t = '', defaultLanguage: r = 'text' }) {
  const n = u.useRef(null),
    [a, i] = u.useState(se.get(r) || 'plaintext'),
    l = u.useCallback((s) => {
      const g = se.get(s.target.value) || 'plaintext'
      i(g)
    }, [])
  return S.jsxs('div', {
    className: 'space-y-2',
    children: [
      S.jsx('div', {
        children: S.jsx(Pt, {
          height: '200px',
          theme: 'vs-dark',
          defaultValue: t,
          defaultLanguage: r,
          language: a,
          onChange: (s) => {
            n.current && (n.current.value = s ?? '')
          },
          options: { automaticLayout: !0 },
        }),
      }),
      S.jsx('div', {
        children: S.jsx('select', {
          defaultValue: r,
          name: `highlighting_language${e}`,
          className: 'acms-admin-form-mini',
          onChange: l,
          children: Et.map((s) =>
            S.jsx('option', { value: s.value, children: s.label }, s.value),
          ),
        }),
      }),
      S.jsx('input', {
        ref: n,
        type: 'hidden',
        name: `highlighting_code${e}`,
        defaultValue: t,
      }),
      S.jsx('input', {
        type: 'hidden',
        name: `unit${e}[]`,
        value: `highlighting_code${e}`,
      }),
      S.jsx('input', {
        type: 'hidden',
        name: `unit${e}[]`,
        value: `highlighting_language${e}`,
      }),
    ],
  })
}
export { Rt as default }
