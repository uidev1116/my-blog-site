import autoLink from './auto-link';
import popup from './popup';
import prettyScroll from './pretty-scroll';
import tocbot from './tocbot';

export default () => {
  autoLink();
  popup();
  prettyScroll();
  tocbot();
};
