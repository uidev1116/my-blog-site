interface Window {
  csrfToken: string
  root: string
  dispatch: (context: Document | Element) => void
  ACMS?: typeof ACMS
}

declare var window: Window
