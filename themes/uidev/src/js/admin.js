/**
 * スタイルの読み込み
 */
import '../scss/site-admin.scss';

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
  });
});
