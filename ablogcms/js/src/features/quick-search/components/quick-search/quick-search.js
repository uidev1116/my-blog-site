/* eslint no-irregular-whitespace: 0 */
import { createRef, Component } from 'react';
import keyboardJS from 'keyboardjs';
import classnames from 'classnames';
import copy from 'copy-to-clipboard';
import unescape from 'unescape';
import styled from 'styled-components';
import axiosLib from '../../../../lib/axios';
import IncrementalSearch from '../../lib/incremental-search';
import Notify from '../../../../components/notify/notify';
import Modal from '../../../../components/modal/modal';
import { ExpireLocalStorage } from '../../../../utils';

const StyledVariableTable = styled.div`
  /* stylelint-disable selector-class-pattern */
  height: 100%;
  font-family: Consolas, 'Courier New', Courier, monospace;

  h1 {
    padding: 1em;
  }

  fieldset {
    margin: 1em;
    background-color: lightyellow;
    border: 2px solid gold;
  }

  fieldset.loop {
    background-color: snow;
    border: 3px solid firebrick;
  }

  fieldset.veil {
    background-color: whitesmoke;
    border: 2px dashed royalblue;
  }

  legend {
    padding: 0.3em;
    font-size: x-large;
    font-weight: bold;
    background-color: lightyellow;
    border: 2px solid gold;
  }

  legend.loop {
    background-color: snow;
    border: 3px solid firebrick;
  }

  legend.veil {
    background-color: whitesmoke;
    border: 2px dashed royalblue;
  }

  var {
    display: block;
    padding: 0.5em;
    margin: 0.5em;
    font-size: large;
    font-style: normal;
    font-weight: bold;
    background-color: white;
    border: 3px double black;
  }

  var.deprecated {
    color: gray;

    &::after {
      /* stylelint-disable-next-line no-irregular-whitespace */
      content: '　※廃止予定';
    }
  }

  var span {
    float: right;
    font-size: 14px;
    color: #777;
  }

  legend span {
    margin-left: 20px;
    font-size: 14px;
    color: #777;
  }

  .textLong {
    width: 420px;
  }

  .textTooLong {
    width: 520px;
  }
  /* stylelint-enable selector-class-pattern */
`;

const StyledQuickSearchModal = styled(Modal)`
  /* stylelint-disable selector-class-pattern */
  font-size: 13px;

  .acms-admin-form textarea {
    width: 100%;
    height: 50%;
    margin: 0;
    font-family: Consolas, 'Courier New', Courier, monospace;
    font-size: 1.3em;
    color: #333;
  }

  .acms-admin-admin-title3 {
    padding: 5px 10px;
    margin: 0 0 10px;
    font-size: 14px;
    color: #333;
    background: #fff;
  }

  .acms-admin-table-admin {
    margin: -10px 0 20px;
    border-spacing: 0;
    border-collapse: collapse;
  }

  .acms-admin-label {
    margin-right: 5px;
    font-size: 11px;
    font-weight: bold;
    color: #fff;
    background-color: #1861d8;
  }

  .mainTitle {
    font-weight: bold;
    color: #333;
    text-decoration: none;

    &:visited {
      color: #333;
    }
  }

  .subTitle {
    color: #5e6c84;

    span {
      font-size: 12px;
      color: #666;
    }
  }

  .hover .acms-admin-label {
    color: #1861d8 !important;
    background-color: #fff;
  }

  .hover .mainTitle {
    color: #333;
  }

  .hover .subTitle {
    color: #5e6c84;
  }

  .hover .subTitle span {
    color: #666;
  }

  .acms-admin-table-admin td {
    display: table-cell;
    padding: 8px 5px;
    line-height: 1.3;
    background-color: #fff;
    border: none;
    transition: auto;
  }

  .acms-admin-modal-footer {
    padding: 10px 0 0;
    margin: 0 -5px;
    border-top: 1px solid #ccc;
  }

  .acms-admin-list-inline {
    margin: 5px 0;
    font-size: 12px;
    color: #666;
    text-align: right;

    li {
      padding-right: 9px;
      font-size: 12px !important;

      kbd {
        padding: 0 5px;
        font-weight: bold;
        background: #eee;
        border-radius: 2px;
      }
    }
  }

  .initial-mark {
    position: relative;
    width: 30px;
    height: 30px;
    margin-right: 5px;
    margin-left: 5px;
    text-align: center;
    background-color: #aaa;
    border-radius: 50px;

    &::after {
      display: inline-block;
      margin-top: 4px;
      margin-left: 1px;
      font-size: 17px;
      color: #fff;
      content: '';
    }
  }

  .initial-b {
    background-color: #d2b8b8;

    &::after {
      content: 'B';
    }
  }

  .initial-c {
    background-color: #b8d2b8;

    &::after {
      content: 'C';
    }
  }

  .initial-e {
    background-color: #b8ccd2;

    &::after {
      content: 'E';
    }
  }

  .initial-m {
    background-color: #c4b8d2;

    &::after {
      content: 'M';
    }
  }

  .initial-m2 {
    background-color: #9fbf91;

    &::after {
      content: 'M';
    }
  }

  .initial-v {
    background-color: #b8d2c9;

    &::after {
      content: 'V';
    }
  }

  .initial-s {
    background-color: #b6c1da;

    &::after {
      content: 'S';
    }
  }

  .initial-g {
    background-color: #adadad;

    &::after {
      content: 'G';
    }
  }

  .customFieldCopied {
    position: fixed;
    inset: -50px 0 auto;
    z-index: 2501;
    width: 100%;
    padding: 10px;
    font-size: 13px;
    color: #fff;
    background: #5690d8;
    border-radius: 0;
    transition: top 0.2s ease-in;

    &.active {
      top: 0;
    }
  }
  /* stylelint-enable selector-class-pattern */
`;

export default class QuickSearch extends Component {
  constructor(props) {
    super(props);
    this.state = {
      lists: [],
      init: false,
      menus: null,
      snippets: null,
      vars: null,
      globalVars: null,
      number: -1,
      isOpen: false,
      keyword: '',
      isMacOs: navigator.userAgent.match(/Mac|PPC/),
      isSnippetsModalOpen: false,
      isVarsModalOpen: false,
      modalContent: '',
      copied: false,
    };
    this.currentItem = null;
    this.box = null;
    this.is = null;

    this.copyButtonRef = createRef();
    this.handleButtonClick = this.handleButtonClick.bind(this);
    this.handleCloseSnippetsModal = this.handleCloseSnippetsModal.bind(this);
    this.handleCloseVarsModal = this.handleCloseVarsModal.bind(this);
    this.handleCopySnippet = this.handleCopySnippet.bind(this);
    this.handleClose = this.handleClose.bind(this);
    this.handleAfterOpen = this.handleAfterOpen.bind(this);
    this.handleAfterClose = this.handleAfterClose.bind(this);
  }

  componentDidUpdate() {
    if (this.currentItem) {
      const boxTop = this.box.getBoundingClientRect().top;
      const boxBottom = boxTop + this.box.offsetHeight;
      const itemTop = this.currentItem.getBoundingClientRect().top;
      const itemBottom = itemTop + this.currentItem.offsetHeight;
      const positionTop = itemTop - boxTop;
      const positionBottom = boxBottom - itemBottom;
      if (positionTop < 0 || positionBottom < 0) {
        this.box.scrollTop += positionTop;
      }
    }
  }

  handleButtonClick() {
    this.toggleDialog();
  }

  componentDidMount() {
    const { buttons } = this.props;
    if (buttons && buttons.length > 0) {
      [].forEach.call(buttons, (button) => {
        button.addEventListener('click', this.handleButtonClick);
      });
    }

    keyboardJS.bind(ACMS.Config.quickSearchCommand, (e) => {
      e.preventDefault();
      this.toggleDialog();
    });

    keyboardJS.bind(['tab', 'down'], (e) => {
      if (this.state.isOpen && !this.state.isSnippetsModalOpen && !this.state.isVarsModalOpen) {
        e.preventDefault();
        this.gotoNextItem();
      }
    });

    keyboardJS.bind(['shift + tab', 'up'], (e) => {
      if (this.state.isOpen && !this.state.isSnippetsModalOpen && !this.state.isVarsModalOpen) {
        e.preventDefault();
        this.gotoPrevItem();
      }
    });

    keyboardJS.bind(['enter'], (e) => {
      if (this.state.isOpen && !this.state.isSnippetsModalOpen && !this.state.isVarsModalOpen) {
        e.preventDefault();
        const item = this.getCurrentItem();
        this.handleClickEvent(item);
      }
    });
  }

  componentWillUnmount() {
    const { buttons } = this.props;
    if (buttons && buttons.length > 0) {
      [].forEach.call(buttons, (button) => {
        button.removeEventListener('click', this.handleButtonClick);
      });
    }
    keyboardJS.reset();
  }

  handleAfterOpen() {
    this.is = new IncrementalSearch();
    this.is.addRequest(
      this.search,
      ACMS.Library.acmsLink({
        bid: ACMS.Config.bid,
      }),
      (lists) => {
        this.setState({ lists });
      }
    );
  }

  handleAfterClose() {
    this.is.destroy();
    this.is = null;
  }

  setDefinedLists() {
    const endpoint = `${ACMS.Library.acmsLink({
      tpl: 'acms-code/all.json',
    })}?cache=${new Date().getTime()}`;

    const key = `acms_big_${ACMS.Config.bid}_quick_search_data`;
    const storage = new ExpireLocalStorage();
    const data = storage.load(key);
    if (data) {
      this.setState({
        menus: data.menus,
        snippets: data.snippets,
        vars: data.vars,
      });
      this.setGlobalVars();
    } else {
      axiosLib
        .get(endpoint)
        .then((res) => {
          this.setState({
            menus: res.data.menus,
            snippets: res.data.snippets,
            vars: res.data.vars,
          });
          storage.save(key, res.data, 1800);
        })
        .then(() => {
          this.setGlobalVars();
        });
    }
  }

  setGlobalVars() {
    const params = new URLSearchParams();
    params.append('ACMS_POST_Search_GlobalVars', true);
    params.append('formToken', window.csrfToken);
    axiosLib({
      method: 'POST',
      url: window.location.href,
      responseType: 'json',
      data: params,
    }).then((res) => {
      res.data.items.push({
        bid: ACMS.Config.bid,
        title: '\u0025{ROOT_TPL}',
        subtitle: ACMS.i18n('quick_search.root_tpl'),
        url: ACMS.Config.rootTpl,
      });
      this.setState({
        globalVars: res.data,
      });
    });
  }

  handleClickEvent(item) {
    const mode = this.getMode();
    if (mode === 'snippets') {
      this.showSnippets(item);
    } else if (mode === 'vars') {
      this.showVars(item);
    } else if (mode === 'global-vars') {
      this.showGlobalVars(item);
    } else {
      this.gotoLink(item);
    }
  }

  gotoLink(item) {
    if (item) {
      location.href = item.url;
    }
  }

  showSnippets(item) {
    if (item) {
      axiosLib.get(item.url).then((res) => {
        const parser = new DOMParser();
        let html = parser.parseFromString(res.data, 'text/html');
        html = html.querySelector('textarea').innerHTML;
        html = unescape(html);
        this.setState({
          isSnippetsModalOpen: true,
          modalContent: html,
        });
      });
    }
  }

  showVars(item) {
    if (item) {
      axiosLib.get(item.url).then((res) => {
        const parser = new DOMParser();
        const html = parser.parseFromString(res.data, 'text/html');
        this.setState({
          isVarsModalOpen: true,
          modalContent: html.body.innerHTML,
        });
      });
    }
  }

  showGlobalVars(item) {
    if (item) {
      copy(item.title);
      this.setState({
        copied: true,
      });
    }
  }

  getMode() {
    const { keyword } = this.state;
    if (ACMS.Config.auth !== 'administrator') {
      return 'normal';
    }
    if (keyword.slice(0, 1) === ';') {
      return 'snippets';
    }
    if (keyword.slice(0, 1) === ':') {
      return 'vars';
    }
    if (keyword.slice(0, 1) === '%') {
      return 'global-vars';
    }
    return 'normal';
  }

  getFilteredSnippets() {
    const { keyword, snippets } = this.state;
    const searchWord = keyword ? keyword.toLowerCase().slice(1) : '';

    if (snippets && snippets.items && searchWord) {
      const items = snippets.items.filter((item) => {
        if (
          item.title.toLowerCase().indexOf(searchWord) !== -1 ||
          item.subtitle.toLowerCase().indexOf(searchWord) !== -1
        ) {
          return true;
        }
        return false;
      });
      return {
        title: snippets.title,
        enTitle: snippets.enTitle,
        items,
      };
    }
    return snippets;
  }

  getFilteredVars() {
    const { keyword, vars } = this.state;
    const searchWord = keyword ? keyword.toLowerCase().slice(1) : '';

    if (vars && vars.items && searchWord) {
      const items = vars.items.filter((item) => {
        if (
          item.title.toLowerCase().indexOf(searchWord) !== -1 ||
          item.subtitle.toLowerCase().indexOf(searchWord) !== -1
        ) {
          return true;
        }
        return false;
      });
      return {
        title: vars.title,
        enTitle: vars.enTitle,
        items,
      };
    }
    return vars;
  }

  getFilteredGlobalVars() {
    const { keyword, globalVars } = this.state;
    const searchWord = keyword ? keyword.toLowerCase().slice(1) : '';

    if (globalVars && globalVars.items && searchWord) {
      const items = globalVars.items.filter((item) => {
        if (
          item.title.toLowerCase().indexOf(searchWord) !== -1 ||
          item.subtitle.toLowerCase().indexOf(searchWord) !== -1
        ) {
          return true;
        }
        return false;
      });
      return {
        title: globalVars.title,
        enTitle: globalVars.enTitle,
        items,
      };
    }
    return globalVars;
  }

  getFilteredMenus() {
    const { keyword, menus } = this.state;
    if (menus && menus.items) {
      const items = menus.items.filter((item) => {
        if (!keyword) {
          return false;
        }
        if (item.title.indexOf(keyword) !== -1 || item.subtitle.indexOf(keyword) !== -1) {
          return true;
        }
        return false;
      });
      return {
        title: menus.title,
        enTitle: menus.enTitle,
        items,
      };
    }
    return menus;
  }

  getCombindLists() {
    const { lists } = this.state;
    const mode = this.getMode();
    if (mode === 'snippets') {
      return [this.getFilteredSnippets()];
    }
    if (mode === 'vars') {
      return [this.getFilteredVars()];
    }
    if (mode === 'global-vars') {
      return [this.getFilteredGlobalVars()];
    }
    const menus = this.getFilteredMenus();
    if (menus) {
      return [menus, ...lists];
    }
    return lists;
  }

  setKeyword(keyword) {
    this.setState({
      keyword,
      number: 0,
    });
  }

  closeDialog() {
    this.setState({
      isOpen: false,
      keyword: '',
      lists: [],
    });
  }

  openDialog() {
    this.setState({
      isOpen: true,
      keyword: '',
      lists: [],
    });
  }

  toggleDialog() {
    const { isOpen, init } = this.state;
    if (init === false) {
      this.setState({
        init: true,
      });
      this.setDefinedLists();
    }
    if (isOpen) {
      this.closeDialog();
    } else {
      this.openDialog();
    }
  }

  handleClose() {
    this.closeDialog();
  }

  gotoNextItem() {
    const { display, number } = this.state;
    const lists = this.getCombindLists();
    const maxNumber = this.getNumber(lists.length, 0) - 1;
    if (display === 'none') {
      return;
    }
    const nextNumber = number + 1 > maxNumber ? 0 : number + 1;
    this.setState({
      number: nextNumber,
    });
  }

  gotoPrevItem() {
    const { isOpen, number } = this.state;
    const lists = this.getCombindLists();
    const maxNumber = this.getNumber(lists.length, 0) - 1;
    if (!isOpen) {
      return;
    }
    const nextNumber = number - 1 < 0 ? maxNumber : number - 1;
    this.setState({
      number: nextNumber,
    });
  }

  getCurrentItem() {
    const { number } = this.state;
    const lists = this.getCombindLists();
    let itemNum = 0;
    let res = false;
    lists.forEach((list) => {
      list.items.forEach((item) => {
        if (number === itemNum) {
          res = item;
        }
        itemNum++;
      });
    });
    return res;
  }

  setNumber(listIndex, index) {
    const number = this.getNumber(listIndex, index);
    this.setState({ number });
  }

  getNumber(listIndex, index) {
    const lists = this.getCombindLists();
    let number = 0;
    while (listIndex > 0) {
      listIndex--;
      if (lists[listIndex]) {
        if (lists[listIndex].items) {
          number += lists[listIndex].items.length;
        }
      }
    }
    return number + index;
  }

  handleCloseSnippetsModal() {
    const { keyword } = this.state;
    this.setState({
      isSnippetsModalOpen: false,
      keyword: keyword.replace(/^(:|;)(.*)/g, '$1'),
    });
    this.search.focus();
  }

  handleCloseVarsModal() {
    const { keyword } = this.state;
    this.setState({
      isVarsModalOpen: false,
      keyword: keyword.replace(/^(:|;)(.*)/g, '$1'),
    });
    this.search.focus();
  }

  handleCopySnippet() {
    const { modalContent } = this.state;
    copy(modalContent);
    this.setState({
      copied: true,
    });
  }

  getInitialClassByName(name) {
    switch (name) {
      case 'Blogs':
        return 'initial-b';
      case 'Categories':
        return 'initial-c';
      case 'Entries':
        return 'initial-e';
      case 'Modules':
        return 'initial-m';
      case 'Menu':
        return 'initial-m2';
      case 'Vars':
        return 'initial-v';
      case 'Snippets':
        return 'initial-s';
      case 'Global vars':
        return 'initial-g';
      default:
        return '';
    }
  }

  render() {
    const { isOpen, number, isMacOs, isSnippetsModalOpen, isVarsModalOpen, modalContent, copied } = this.state;
    const lists = this.getCombindLists();
    const mode = this.getMode();

    return (
      <>
        <StyledQuickSearchModal
          isOpen={isOpen}
          onClose={this.handleClose}
          id="quick-search-dialog"
          className="acms-admin-modal-middle"
          dialogClassName="acms-admin-modal-quick-search"
          onAfterOpen={this.handleAfterOpen}
          onAfterClose={this.handleAfterClose}
          aria-labelledby="acms-qucik-search-dialog-title"
        >
          <StyledQuickSearchModal.Body>
            <div className="acms-admin-form" style={{ paddingTop: '15px', paddingBottom: '15px' }}>
              <label className="acms-admin-width-max">
                <span id="acms-qucik-search-dialog-title" className="acms-admin-hide-visually">
                  {ACMS.i18n('quick_search.title')}
                </span>
                <input
                  type="text"
                  ref={(ref) => {
                    this.search = ref;
                  }}
                  style={{ fontSize: '24px', fontWeight: 'bold' }}
                  placeholder={ACMS.i18n('quick_search.input_placeholder')}
                  className="acms-admin-form-width-full acms-admin-form-large acms-admin-margin-bottom-small"
                  onInput={(e) => {
                    this.setKeyword(e.target.value);
                  }}
                />
              </label>
            </div>
            <div
              ref={(element) => {
                this.box = element;
              }}
              className="acms-admin-modal-middle-scroll"
            >
              {lists.map((list, listIndex) => (
                <div key={`label-${list.enTitle}`}>
                  {list && list.items.length > 0 && (
                    <div>
                      <h2 className="acms-admin-admin-title3" style={{ background: '#FFF' }}>
                        {list.title}
                      </h2>
                      <table className="acms-admin-table-admin acms-admin-form acms-admin-table-hover">
                        <tbody key={`body-${list.enTitle}`}>
                          {list.items.map((item, index) => (
                            <tr
                              key={this.getNumber(listIndex, index)}
                              ref={(element) => {
                                if (this.getNumber(listIndex, index) === number) this.currentItem = element;
                              }}
                              onClick={this.handleClickEvent.bind(this, item)}
                              onMouseMove={this.setNumber.bind(this, listIndex, index)}
                              className={classnames({ hover: this.getNumber(listIndex, index) === number })}
                            >
                              <td style={{ width: '1px', wordBreak: 'break-all' }}>
                                <div className={classnames('initial-mark', this.getInitialClassByName(list.enTitle))} />
                              </td>
                              <td>
                                <a className="mainTitle" href={item.url}>
                                  {item.title}
                                </a>
                                <div className="subTitle">
                                  {item.subtitle} <span>{item.blogName}</span>
                                </div>
                              </td>
                              {mode !== 'normal' ? (
                                <td style={{ textAlign: 'right', wordBreak: 'break-all' }}>
                                  {mode === 'global-vars' && <span style={{ paddingRight: '10px' }}>{item.url}</span>}
                                </td>
                              ) : (
                                <td style={{ width: '1px', textAlign: 'right', whiteSpace: 'nowrap' }}>
                                  {item.bid && (
                                    <span className="acms-admin-label">
                                      bid:
                                      {item.bid}
                                    </span>
                                  )}
                                  {item.cid && (
                                    <span className="acms-admin-label">
                                      cid:
                                      {item.cid}
                                    </span>
                                  )}
                                  {item.eid && (
                                    <span className="acms-admin-label">
                                      eid:
                                      {item.eid}
                                    </span>
                                  )}
                                  {item.mid && (
                                    <span className="acms-admin-label">
                                      mid:
                                      {item.mid}
                                    </span>
                                  )}
                                </td>
                              )}
                            </tr>
                          ))}
                        </tbody>
                      </table>
                    </div>
                  )}
                </div>
              ))}
            </div>
          </StyledQuickSearchModal.Body>
          <StyledQuickSearchModal.Footer>
            <ul className="acms-admin-list-inline">
              <li>
                <kbd>tab</kbd> or
                <kbd>⇅</kbd> {ACMS.i18n('quick_search.choice')}
              </li>
              <li>
                <kbd>↵</kbd> {ACMS.i18n('quick_search.move')}
              </li>
              <li>
                <kbd>esc</kbd> {ACMS.i18n('quick_search.close')}
              </li>
              {ACMS.Config.auth === 'administrator' && (
                <>
                  <li>
                    <kbd>{isMacOs ? <span>⌘K</span> : <span>ctl+k</span>}</kbd> {ACMS.i18n('quick_search.open')}
                  </li>
                  <li>
                    <kbd>;</kbd> {ACMS.i18n('quick_search.snippets')}
                  </li>
                  <li>
                    <kbd>:</kbd> {ACMS.i18n('quick_search.vars')}
                  </li>
                  <li>
                    <kbd>%</kbd> {ACMS.i18n('quick_search.g_vars')}
                  </li>
                </>
              )}
            </ul>
          </StyledQuickSearchModal.Footer>
        </StyledQuickSearchModal>
        <Modal
          isOpen={isSnippetsModalOpen}
          onClose={this.handleCloseSnippetsModal}
          focusTrapOptions={{ initialFocus: () => this.copyButtonRef.current }}
        >
          <Modal.Header>{ACMS.i18n('quick_search.snippets')}</Modal.Header>
          <Modal.Body>
            <div className="acms-admin-form">
              <div style={{ paddingTop: '10px', paddingBottom: '10px' }}>
                <textarea rows="10" value={modalContent} readOnly style={{ width: '100%', fontSize: '16px' }} />
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <div style={{ marginRight: '20px' }}>
              <button type="button" onClick={this.handleCloseSnippetsModal} className="acms-admin-btn">
                {ACMS.i18n('quick_search.close')}
              </button>
              <button
                ref={this.copyButtonRef}
                type="button"
                onClick={this.handleCopySnippet}
                className="acms-admin-btn"
              >
                {ACMS.i18n('quick_search.copy')}
              </button>
            </div>
          </Modal.Footer>
        </Modal>
        <Modal isOpen={isVarsModalOpen} onClose={this.handleCloseVarsModal}>
          <Modal.Header>{ACMS.i18n('quick_search.vars')}</Modal.Header>
          <Modal.Body>
            <div className="acms-admin-form">
              <div style={{ paddingTop: '10px', paddingBottom: '10px' }}>
                {/* eslint-disable-next-line react/no-danger */}
                <StyledVariableTable dangerouslySetInnerHTML={{ __html: modalContent }} />
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <div style={{ marginRight: '20px' }}>
              <button type="button" onClick={this.handleCloseVarsModal} className="acms-admin-btn">
                {ACMS.i18n('quick_search.close')}
              </button>
            </div>
          </Modal.Footer>
        </Modal>
        <Notify
          message={ACMS.i18n('quick_search.copy_message')}
          show={copied}
          onFinish={() => {
            this.setState({
              copied: false,
            });
          }}
        />
      </>
    );
  }
}
