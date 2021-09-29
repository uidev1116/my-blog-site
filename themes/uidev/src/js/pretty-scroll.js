import domContentLoaded from 'dom-content-loaded';
import PrettyScroll from 'pretty-scroll';

export default () => {
  domContentLoaded(() => {
    new PrettyScroll('.js-pretty-scroll', {
      container: '.js-container',
      breakpoint: 960,
      offsetTop: 24,
    });
  });
};
