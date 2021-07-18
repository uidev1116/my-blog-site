import domContentLoaded from 'dom-content-loaded';
import $ from 'jquery';

export default () => {
  domContentLoaded(() => {
    // スマホ用メニュー
    const $mobileNavTrigger = $('.js-mobile-nav-btn');
    const $mobileNavContents = $('.js-mobile-nav');
    $($mobileNavTrigger).click(() => {
      $('body').toggleClass('is-locked');
      const $mobileNavAttr = $($mobileNavTrigger).attr('aria-expanded');
      if ($mobileNavAttr === 'true') {
        $($mobileNavTrigger).attr('aria-expanded', false);
        $($mobileNavContents).delayRemoveClass('is-opened', 1).delayRemoveClass('is-active', 100);
      } else {
        $($mobileNavTrigger).attr('aria-expanded', true);
        $($mobileNavContents).delayAddClass('is-active', 1).delayAddClass('is-opened', 100);
      }
      $($mobileNavContents).find('[href*="#"]').click(() => {
        $($mobileNavTrigger).attr('aria-expanded', false);
        $($mobileNavContents).delayRemoveClass('is-opened', 1).delayRemoveClass('is-active', 100);
        $('body').removeClass('is-locked');
      });
    });
  });
};
