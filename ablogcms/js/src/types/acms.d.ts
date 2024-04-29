interface acms {
  Dispatch: any
  Dispatch2: any
  Library: any
  Config: any
  Load: any
  addListener(eventName: string, fn: Function): void
  i18n(key: string): string
}

declare var ACMS: acms
