import 'trumbowyg'
import 'trumbowyg/dist/plugins/colors/trumbowyg.colors'
import 'trumbowyg/dist/plugins/fontsize/trumbowyg.fontsize'
import 'trumbowyg/dist/plugins/lineheight/trumbowyg.lineheight'
import 'trumbowyg/dist/plugins/table/trumbowyg.table'
import 'trumbowyg/dist/langs/ja'

import 'trumbowyg/dist/plugins/colors/ui/trumbowyg.colors.css'
import 'trumbowyg/dist/plugins/table/ui/trumbowyg.table.css'
import 'trumbowyg/dist/ui/trumbowyg.css'
import 'trumbowyg/dist/ui/icons.svg'

export default (elm, config) => {
  $(() => {
    $.trumbowyg.svgPath = `${ACMS.Config.root}js/dest/assets/icons.svg`
    $(elm).trumbowyg(config)
  })
}
