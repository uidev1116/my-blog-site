import domContentLoaded from 'dom-content-loaded';
import tocbot from 'tocbot';
import 'tocbot/src/scss/tocbot.scss';

export default () => {
  domContentLoaded(() => {
    tocbot.init({
      tocSelector: '.js-toc',
      contentSelector: '.js-toc-content',
      headingSelector: 'h2, h3, h4',
      hasInnerContainers: true,
      linkClass: 'p-sub-column-toc__link',
      listClass: 'p-sub-column-toc__list',
      listItemClass: 'p-sub-column-toc__item',
      collapseDepth: 6,
    });
  });
};
