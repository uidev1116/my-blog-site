import { Suspense, lazy } from 'react'
import { renderReact } from '../utlis/react'
import { Language } from '../features/HighlightingCodeUnit/types'

export default function setupHighlightingCodeUnit(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>(
    '.js-highlighting-code-unit',
  )
  if (elements.length === 0) {
    return
  }

  const HighlightingCodeUnit = lazy(
    () =>
      import(
        '../features/HighlightingCodeUnit/components/HighlitingCodeUnit/HighlitingCodeUnit'
      ),
  )
  elements.forEach((element) => {
    const {
      id = '',
      defaultCode = '',
      defaultLanguage = 'text',
    } = element.dataset

    const props: React.ComponentPropsWithoutRef<typeof HighlightingCodeUnit> = {
      id,
      defaultCode,
      defaultLanguage: (defaultLanguage as Language) || 'text',
    }
    renderReact(
      <Suspense>
        <HighlightingCodeUnit {...props} />
      </Suspense>,
      element,
    )
  })
}
