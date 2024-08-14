import {
  W as u,
  u as g,
  v as f,
  w as _,
  x as h,
  y as m,
  z as W,
  A as n,
  B as s,
  _ as i,
} from './vendor-b4af7ed9.js'
window.MonacoEnvironment = {
  getWorker(t, r) {
    return (
      console.log(r),
      r === 'json'
        ? new u()
        : r === 'css' || r === 'scss' || r === 'less'
        ? new g()
        : r === 'html' || r === 'handlebars' || r === 'razor'
        ? new f()
        : r === 'typescript' || r === 'javascript'
        ? new _()
        : new h()
    )
  },
}
m.typescript.typescriptDefaults.setEagerModelSync(!0)
function E(t, r, e) {
  const o = W(r, e)
  r._reactRoot && r._reactRoot.unmount(),
    (r._reactRoot = o),
    o.render(n.jsx(s.StrictMode, { children: t }))
}
function j(t) {
  const r = t.querySelectorAll('.js-highlighting-code-unit')
  if (r.length === 0) return
  const e = s.lazy(() =>
    i(
      () => import('./HighlitingCodeUnit-3519dcb1.js'),
      [
        './HighlitingCodeUnit-3519dcb1.js',
        './vendor-b4af7ed9.js',
        './vendor-f0151ef6.css',
      ],
      import.meta.url,
    ),
  )
  r.forEach((o) => {
    const {
        id: a = '',
        defaultCode: c = '',
        defaultLanguage: d = '',
      } = o.dataset,
      p = { id: a, defaultCode: c, defaultLanguage: d }
    E(n.jsx(s.Suspense, { children: n.jsx(e, { ...p }) }), o)
  })
}
ACMS.addListener('configLoad', async () => {
  const { default: t } = await i(
    () => import('./admin-0f1044b8.js'),
    [],
    import.meta.url,
  )
  Object.entries(t).forEach(([r, e]) => {
    ACMS.Config[r] = e
  })
})
ACMS.Ready(() => {
  ACMS.addListener('acmsAddUnit', (t) => {
    j(t.obj.item)
  })
})
