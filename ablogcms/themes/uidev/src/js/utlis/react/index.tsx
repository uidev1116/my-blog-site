import { StrictMode } from 'react'
import { Root, RootOptions, createRoot } from 'react-dom/client'

interface ReactContainerElement extends HTMLElement {
  _reactRoot?: Root
}

export function renderReact(
  component: JSX.Element,
  element: ReactContainerElement,
  options?: RootOptions,
) {
  const root = createRoot(element, options)
  if (element._reactRoot) {
    element._reactRoot.unmount()
  }
  element._reactRoot = root
  root.render(<StrictMode>{component}</StrictMode>)
}
