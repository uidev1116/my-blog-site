interface acms {
  Dispatch: any
  Library: any
  Config: any
  addListener(eventName: string, fn: Function): void;
}

declare var ACMS: acms;

