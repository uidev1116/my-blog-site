/* eslint @typescript-eslint/no-explicit-any: 0 */

/// <reference types="i18next" />

interface acms {
  Dispatch: any;
  Dispatch2: any;
  Library: any;
  Config: any;
  Load: any;
  dispatchEvent(eventName: string, dom?: HTMLElement | Document, obj?: object): void;
  addListener(eventName: string, fn: (event: any) => void): void;
  i18n: import('i18next').TFunction & {
    lng: string;
  };
}

declare let ACMS: acms;
