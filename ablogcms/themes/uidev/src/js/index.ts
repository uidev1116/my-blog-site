import 'vite/modulepreload-polyfill'
import domContentLoaded from 'dom-content-loaded'
import fonts from './fonts'
import {
  validator,
  externalLinks,
  smartPhoto,
  lazyLoad,
  // inView,
  scrollHint,
  openStreetMap,
  // datePicker,
  postInclude,
  // pdfPreview,
  unitGroupAlign,
} from './lib/build-in' // ToDo: いらないものは削除する

/**
 * スタイルの読み込み
 */
// import 'normalize.css/normalize.css';
import '../scss/index.scss'

/**
 * FontAwesome 5
 */
fonts()

/**
 * Root
 */
window.root = '/'

/**
 * BuildInJs
 * ToDo: 使わない組み込みJSはコメントアウト
 */
if (window.ACMS === undefined) {
  window.dispatch = (context) => {
    validator(context)
    // linkMatchLocation(context);
    externalLinks(context)
    // scrollTo(context);
    // alertUnload(context);
    smartPhoto(context)
    lazyLoad(context)
    // inView(context);
    // modalVideo(context);
    scrollHint(context)
    // googleMap(context);
    openStreetMap(context)
    // datePicker(context);
    postInclude(context)
    // pdfPreview(context);
    // focusedImage(context);
    unitGroupAlign(context)
  }
  window.dispatch(document)
}

/**
 * Content Ready
 */
domContentLoaded(() => {})
