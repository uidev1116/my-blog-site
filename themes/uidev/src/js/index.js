import domContentLoaded from 'dom-content-loaded';
import Dispatcher from 'a-dispatcher';
import './lib/polyfill';
import fonts from './fonts';
import {
  validator, linkMatchLocation, externalLinks, scrollTo,
  alertUnload, smartPhoto, lazyLoad, inView,
  modalVideo, scrollHint, googleMap, openStreetMap,
  datePicker, postInclude, pdfPreview, focusedImage, unitGroupAlign,
} from './lib/build-in'; // ToDo: いらないものは削除する
import offcanvas from './offcanvas';

/**
 * スタイルの読み込み
 */
import 'normalize.css/normalize.css';
import '../scss/site.scss';

/**
 * FontAwesome 5
 */
fonts();

/**
 * Root
 */
window.root = '/';

/**
 * BuildInJs
 * ToDo: 使わない組み込みJSはコメントアウト
 */
if (window.ACMS === undefined) {
  window.dispatch = (context) => {
    validator(context);
    linkMatchLocation(context);
    externalLinks(context);
    scrollTo(context);
    alertUnload(context);
    smartPhoto(context);
    lazyLoad(context);
    inView(context);
    modalVideo(context);
    scrollHint(context);
    googleMap(context);
    openStreetMap(context);
    datePicker(context);
    postInclude(context);
    pdfPreview(context);
    focusedImage(context);
    unitGroupAlign(context);
  };
  window.dispatch(document);
}

/**
 * Dispatcher
 */
const dispatcher = new Dispatcher();

// ダイナミックインポート
dispatcher.addRoute('^/app.html$', async () => {
  const { default: appPage } = await import(/* webpackChunkName: "app" */'./containers/app');
  appPage();
});

// 通常のバンドル
// dispatcher.addRoute('^/example/$', examplePage);

dispatcher.run(window.location.pathname);

// 全ページで読み込むjQuery使用コード
offcanvas();

/**
 * Content Ready
 */
domContentLoaded(() => {
  // $(() => {
  // 郵便番号の「-」の挿入
  // $('.js-insert-hyphen').blur(function(){
  //     function insertStr(str, index, insert) {
  //         return str.slice(0, index) + insert + str.slice(index, str.length);
  //     }
  //     const zip = $(this).val();
  //     if (zip.length > 6 && !zip.match('-')) {
  //         var zipHyphen = insertStr(zip, 3, '-');
  //         $(this).val(zipHyphen);
  //     }
  // });
  // });
});
