import type { ReactNode } from 'react'

export type Align = 'right' | 'center' | 'left'

export type Btn = {
  className: string
  tag: string
  icon: React.ReactNode
  tooltip: string
}

export type TableEditorProps = {
  html: string
  btns?: Btn[]
  onChange?: (html: string) => void
  showBtnList: boolean
  relAttrForTargetBlank: string
  lang: string
  mark: {
    align: {
      default: Align
      left: Align
      center: Align
      right: Align
    }
    btn: {
      group: string
      item: string
      itemActive: string
    }
    label: string
    actionGroup: string
    selector: {
      self: string
    }
    icon: {
      alignLeft: ReactNode
      alignCenter: ReactNode
      alignRight: ReactNode
      merge: ReactNode
      split: ReactNode
      td: ReactNode
      th: ReactNode
      undo: ReactNode
    }
  }
  message: {
    mergeCells: string
    splitCell: string
    changeToTh: string
    changeToTd: string
    alignLeft: string
    alignCenter: string
    alignRight: string
    addColumnLeft: string
    addColumnRight: string
    removeColumn: string
    addRowTop: string
    addRowBottom: string
    removeRow: string
    source: string
    mergeCellError1: string
    mergeCellConfirm1: string
    pasteError1: string
    splitError1: string
    splitError2: string
    splitError3: string
    closeLabel: string
    targetBlank: string
    targetBlankLabel: string
    addLinkTitle: string
    updateLinkTitle: string
    addLink: string
    updateLink: string
    removeLink: string
    linkUrl: string
    linkLabel: string
  }
  showTargetBlankUI: boolean
}

export type TableEditorConfig = {
  mark: string
  props: Omit<TableEditorProps, 'html' | 'onChange'>
}
