import domContentLoaded from 'dom-content-loaded';
import $ from 'jquery';

export default () => {
  domContentLoaded(() => {
    // ページ上部へ戻るボタンの表示の設定
    const $nav = $('.page-top-btn');
    const offset = '50';
    // const footer = $(document).height() - $(window).height() - 500;
    $(window).scroll(() => {
      if ($(window).scrollTop() > offset) {
        $nav.addClass('page-top-btn-appear');
      } else {
        $nav.removeClass('page-top-btn-appear');
      }
    });

    $.fn.delayAddClass = function (className, delay) { // eslint-disable-line func-names
      return $(this).delay(delay).queue(function (next) { // eslint-disable-line func-names
        $(this).addClass(className);
        next();
      });
    };
    $.fn.delayRemoveClass = function (className, delay) { // eslint-disable-line func-names
      return $(this).delay(delay).queue(function (next) { // eslint-disable-line func-names
        $(this).removeClass(className);
        next();
      });
    };
  });
};
