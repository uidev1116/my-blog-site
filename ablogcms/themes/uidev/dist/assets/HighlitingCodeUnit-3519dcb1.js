import { B as t, A as l, G as o } from './vendor-b4af7ed9.js'
const h = () => {
    const e = t.useRef(!0)
    return e.current ? ((e.current = !1), !0) : e.current
  },
  d = h,
  g = (e) => {
    t.useEffect(e, [])
  },
  v = g,
  f = {}
function m({ value: e, onChange: c, options: r, ...s }) {
  const n = t.useRef(null)
  d()
  const u = t.useRef(null)
  return (
    v(
      () => (
        u.current &&
          n.current === null &&
          (n.current = o.create(u.current, { ...f, ...r, value: e })),
        () => {
          n.current &&
            (n.current.dispose(), (n.current = null), console.log('dispose'))
        }
      ),
    ),
    l.jsx('div', { ref: u, ...s })
  )
}
const b = [
  { value: '', label: '言語を選択してください' },
  { value: 'html', label: 'HTML' },
  { value: 'css', label: 'CSS' },
  { value: 'sass', label: 'Sass' },
  { value: 'scss', label: 'SCSS' },
  { value: 'postcss', label: 'PostCSS' },
  { value: 'js', label: 'JavaScript' },
  { value: 'ts', label: 'TypeScript' },
  { value: 'jsx', label: 'JSX' },
  { value: 'tsx', label: 'TSX' },
  { value: 'vue', label: 'Vue' },
  { value: 'php', label: 'PHP' },
  { value: 'json', label: 'JSON' },
  { value: 'sh', label: 'Shell' },
  { value: 'yaml', label: 'YAML' },
  { value: 'xml', label: 'XML' },
  { value: 'csv', label: 'CSV' },
  { value: 'md', label: 'Markdown' },
  { value: 'sql', label: 'SQL' },
  { value: 'text', label: 'Plain Text' },
]
function x({ id: e, defaultCode: c = '', defaultLanguage: r = '' }) {
  const s = t.useRef(null),
    [n, u] = t.useState(r),
    i = t.useCallback((a) => {
      u(a.target.value)
    }, [])
  return l.jsx('table', {
    className: 'acms-admin-table-admin-edit',
    children: l.jsxs('tbody', {
      children: [
        l.jsx('tr', {
          children: l.jsxs('td', {
            children: [
              l.jsx(m, {
                style: { height: '100px' },
                className: 'acms-admin-form-width-full',
                value: c,
                onChange: (a) => {
                  s.current && (s.current.value = a)
                },
                options: { language: 'html' },
              }),
              l.jsx('input', {
                ref: s,
                type: 'hidden',
                name: `highlighting_code${e}`,
                defaultValue: c,
              }),
              l.jsx('input', {
                type: 'hidden',
                name: `unit${e}[]`,
                value: `highlighting_code${e}`,
              }),
            ],
          }),
        }),
        l.jsx('tr', {
          children: l.jsxs('td', {
            children: [
              l.jsx('select', {
                defaultValue: r,
                name: `highlighting_language${e}`,
                className: 'acms-admin-form-mini',
                onChange: i,
                children: b.map((a) =>
                  l.jsx(
                    'option',
                    { value: a.value, children: a.label },
                    a.value,
                  ),
                ),
              }),
              l.jsx('input', {
                type: 'hidden',
                name: `unit${e}[]`,
                value: `highlighting_language${e}`,
              }),
            ],
          }),
        }),
      ],
    }),
  })
}
export { x as default }
