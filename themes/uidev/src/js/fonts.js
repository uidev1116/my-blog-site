import { library, dom, config } from '@fortawesome/fontawesome-svg-core';
import {
  faSearch,
  faRss,
  faLink,
  faPlus,
} from '@fortawesome/free-solid-svg-icons';
import {
  faFacebook,
  faTwitter,
  faInstagram,
  faGithub,
  faYoutube,
} from '@fortawesome/free-brands-svg-icons';

/**
 * fontawesome5
 *
 * 必要なアイコンのみインポートして、library.add で追加します。命名規則はクラス名（ケバブケース）をキャメルケースにしたものです。
 * e.g. <i class="fas fa-sign-out-alt"></i>   -->   faSignOutAlt
 * IconList: https://fontawesome.com/icons
 */
export default () => {
  config.searchPseudoElements = false;
  library.add(
    faSearch,
    faFacebook,
    faTwitter,
    faInstagram,
    faGithub,
    faYoutube,
    faRss,
    faLink,
    faPlus,
  );
  dom.watch();
};
