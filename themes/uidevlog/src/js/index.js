import domContentLoaded from 'dom-content-loaded';
import Dispatcher from 'a-dispatcher';
import lozad from 'lozad';
import MasonryLayout from 'masonry-layout';
import ImagesLoaded from 'imagesloaded';
import './lib/polyfill';
import fonts from './fonts';
import {
  validator, linkMatchLocation, externalLinks, scrollTo,
  alertUnload, smartPhoto, lazyLoad, inView,
  modalVideo, scrollHint, googleMap, openStreetMap,
  datePicker, postInclude, pdfPreview, focusedImage,
} from './lib/build-in'; // ToDo: いらないものは削除する
import pageTopBtn from './page-top-btn';
import mobileNav from './mobile-nav';
// import examplePage from './example';

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
pageTopBtn();
mobileNav();

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

  /**
   * pintarest風レイアウト
   */
  const observer = lozad('.js-images-loaded-container', { // eslint-disable-line no-undef
    loaded: (container) => {
      const images = container.querySelectorAll('img');
      [].forEach.call(images, (img) => {
        const src = img.getAttribute('data-src');
        if (src) {
          img.setAttribute('src', src);
        }
      });
      ImagesLoaded(container, () => { // eslint-disable-line no-undef
        const grid = container.querySelector('.js-masonry-grid');
        if (grid) {
          new MasonryLayout(grid, { // eslint-disable-line no-undef, no-new
            itemSelector: '.js-masonry-grid-item',
          });
        }
      });
    },
  });
  observer.observe();

  /**
   * ポップアップ
   */
  function popupImage() {
    const popup = document.getElementById('js-popup');
    if (!popup) return;

    const blackBg = document.getElementById('js-black-bg');
    const closeBtn = document.getElementById('js-close-btn');
    const showBtn = document.getElementById('js-show-popup');

    closePopUp(blackBg); // eslint-disable-line no-use-before-define
    closePopUp(closeBtn); // eslint-disable-line no-use-before-define
    closePopUp(showBtn); // eslint-disable-line no-use-before-define
    function closePopUp(elem) {
      if (!elem) return;
      elem.addEventListener('click', () => {
        popup.classList.toggle('is-show');
      });
    }
  }
  popupImage();
});
