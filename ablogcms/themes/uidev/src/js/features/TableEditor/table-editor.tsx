/* eslint @typescript-eslint/ban-ts-comment: 0 */
import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import deepmerge from 'deepmerge'

import type { TableEditorConfig } from './types'

const setupTableEditor = async (
  context: Document | Element,
  option: Partial<TableEditorConfig> = {},
) => {
  const [{ TableEditor }] = await Promise.all([
    import('react-table-editor'),
    import('./table-editor.scss'),
  ])
  const { mark, props } = deepmerge(
    ACMS.Config.tableEditor as TableEditorConfig,
    option,
  )
  const elements = context.querySelectorAll(mark as string)
  elements.forEach((element) => {
    const inputMark = element.getAttribute('data-input') as string
    const input = context.querySelector<HTMLInputElement>(inputMark as string)
    if (input === null) {
      throw new Error('Not Found input field')
    }
    const root = createRoot(element)
    if (element.hasAttribute('data-rendered')) {
      root.unmount()
    }

    const handleChange = (html: string) => {
      input.value = html
    }

    root.render(
      <StrictMode>
        {/* @ts-ignore */}
        <TableEditor html={input.value} onChange={handleChange} {...props} />
      </StrictMode>,
    )
    element.setAttribute('data-rendered', '')
  })
}

export default setupTableEditor
