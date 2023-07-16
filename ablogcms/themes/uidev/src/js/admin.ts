import { setupTableEditor } from './features'

/**
 * スタイルの読み込み
 */
import '../scss/admin.scss'

/**
 * ACMS Config Load
 */
ACMS.addListener('configLoad', async () => {
  const { default: config } = await import('./config/admin')
  Object.entries(config).forEach(([key, value]) => {
    ACMS.Config[key] = value
  })
})

/**
 * ACMS Ready
 */
ACMS.Ready(() => {
  /**
   * LiteEditor
   */
  ACMS.Config.LiteEditorConf.btnOptions.push({
    label: 'コード',
    tag: 'code',
  })

  ACMS.addListener('acmsAddUnit', (event) => {
    setupTableEditor(event.obj.item as Element)
  })
})
