import { setupHighlightingCodeUnit } from './setups'

import './globals.css'

/**
 * ACMS Ready
 */
ACMS.Ready(() => {
  ACMS.addListener('acmsAddUnit', (event) => {
    setupHighlightingCodeUnit(event.obj.item as Element)
    // setupTableEditor(event.obj.item as Element)
  })
})
