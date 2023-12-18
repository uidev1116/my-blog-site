interface acms {
  Dispatch: any
  Dispatch2: any
  Library: any
  Config: any
  Load: any
  Ready(fn: Function): void
  addListener(eventName: string, fn: (event: any) => void): void
  i18n(key: string): string
}

declare var ACMS: acms
