import { useRef, useState, useCallback } from 'react'
import MonacoEditor from '@monaco-editor/react'
import type { Language } from '../../types'

interface HighlightingCodeUnitProps {
  id: string
  defaultCode: string
  defaultLanguage: Language
}

type MonacoLanguage =
  | 'typescript'
  | 'javascript'
  | 'json'
  | 'markdown'
  | 'shell'
  | 'yaml'
  | 'css'
  | 'html'
  | 'xml'
  | 'csv'
  | 'php'
  | 'scss'
  | 'sql'
  | 'plaintext'

const langMap = new Map<Language, MonacoLanguage>([
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
])

const options: { value: Language; label: string }[] = [
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

export default function HighlightingCodeUnit({
  id,
  defaultCode = '',
  defaultLanguage = 'text',
}: HighlightingCodeUnitProps) {
  const codeInputRef = useRef<HTMLInputElement>(null)
  const [monacoLanguage, setMonacoLanguage] = useState<MonacoLanguage>(
    langMap.get(defaultLanguage as Language) || 'plaintext',
  )

  const handleLanguageChange = useCallback(
    (event: React.ChangeEvent<HTMLSelectElement>) => {
      const newMonacoLanguage =
        langMap.get(event.target.value as Language) || 'plaintext'
      setMonacoLanguage(newMonacoLanguage)
    },
    [],
  )
  return (
    <div className="space-y-2">
      <div>
        <MonacoEditor
          height="200px"
          theme="vs-dark"
          defaultValue={defaultCode}
          defaultLanguage={defaultLanguage}
          language={monacoLanguage}
          onChange={(newValue) => {
            if (codeInputRef.current) {
              codeInputRef.current.value = newValue ?? ''
            }
          }}
          options={{
            automaticLayout: true,
          }}
        />
      </div>
      <div>
        <select
          defaultValue={defaultLanguage}
          name={`highlighting_language${id}`}
          className="acms-admin-form-mini"
          onChange={handleLanguageChange}
        >
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      </div>
      <input
        ref={codeInputRef}
        type="hidden"
        name={`highlighting_code${id}`}
        defaultValue={defaultCode}
      />
      <input
        type="hidden"
        name={`unit${id}[]`}
        value={`highlighting_code${id}`}
      />
      <input
        type="hidden"
        name={`unit${id}[]`}
        value={`highlighting_language${id}`}
      />
    </div>
  )
}
