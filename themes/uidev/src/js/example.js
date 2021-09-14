import domContentLoaded from 'dom-content-loaded';
import $ from 'jquery';
import 'slick-carousel';
import 'slick-carousel/slick/slick.css';

export default () => {
  domContentLoaded(() => {
    // トップページのスライダーの設定
    const $slider = $('.js-slider');
    $slider.slick({
      accessibility: true,
      dots: true,
      arrows: true,
      prevArrow: '<button type="button" class="slick-prev">前へ</button>',
      nextArrow: '<button type="button" class="slick-next">次へ</button>',
      autoplay: true,
      autoplaySpeed: 3000,
      pauseOnHover: false,
      responsive: [
        {
          breakpoint: 480,
          settings: {
            arrows: false,
          },
        },
      ],
    });
  });
};
