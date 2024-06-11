import React, { Component, Fragment } from 'react'
import keyboardJS from 'keyboardjs'
import classnames from 'classnames'
import copy from 'copy-to-clipboard'
import unescape from 'unescape'
import axiosLib from '../lib/axios'
import IncrementalSearch from '../lib/incremental-search'
import ACMSModal from './modal'
import Notify from './notify'
import { ExpireLocalStorage } from '../lib/utility'

const FreeStyle = require('free-style')

const Style = FreeStyle.create()
const styleValiableTable = Style.registerStyle({
  fontFamily: 'Consolas, Courier New ,Courier, monospace',
  height: '100%',
  h1: {
    padding: '1em',
  },
  fieldset: {
    margin: '1em',
    border: '2px solid gold',
    backgroundColor: 'lightyellow',
  },
  'fieldset.loop': {
    border: '3px solid firebrick',
    backgroundColor: 'snow',
  },
  'fieldset.veil': {
    border: '2px dashed royalblue',
    backgroundColor: 'whitesmoke',
  },
  legend: {
    padding: '0.3em',
    fontWeight: 'bold',
    fontSize: 'x-large',
    border: '2px solid gold',
    backgroundColor: 'lightyellow',
  },
  'legend.loop': {
    border: '3px solid firebrick',
    backgroundColor: 'snow',
  },
  'legend.veil': {
    border: '2px dashed royalblue',
    backgroundColor: 'whitesmoke',
  },
  'legend vars': {
    display: 'inline',
  },
  var: {
    display: 'block',
    margin: '0.5em',
    padding: '0.5em',
    fontStyle: 'normal',
    fontWeight: 'bold',
    fontSize: 'large',
    border: '3px double black',
    backgroundColor: 'white',
  },
  'var.deprecated': {
    color: 'gray',
  },
  'var.deprecated:after': {
    content: '　※廃止予定',
  },
  'var span': {
    float: 'right',
    fontSize: '14px',
    color: '#777',
  },
  'legend span': {
    marginLeft: '20px',
    fontSize: '14px',
    color: '#777',
  },
  '.textLong': {
    width: '420px',
  },
  '.textTooLong': {
    width: '520px',
  },
})

const styleQuickSearch = Style.registerStyle({
  '.acms-admin-modal': {
    fontSize: '13px',
    backgroundColor: 'rgba(0,0,0,.5)',
  },
  '.acms-admin-form textarea': {
    width: '100%',
    fontSize: '1.3em',
    fontFamily: 'Consolas,Courier New,Courier,monospace',
    margin: '0',
    height: '50%',
    color: '#333',
  },
  '.acms-admin-admin-title3': {
    margin: '0 0 10px',
    padding: '5px 10px',
    background: '#fff',
    color: '#333',
    fontSize: '14px',
  },
  '.acms-admin-table-admin': {
    borderCollapse: 'collapse',
    borderSpacing: '0',
    margin: '-10px 0 20px 0',
  },
  '.acms-admin-label': {
    marginRight: '5px',
    backgroundColor: '#1861D8',
    color: '#ffffff',
    fontWeight: 'bold',
    fontSize: '11px',
  },
  '.mainTitle': {
    textDecoration: 'none',
    fontWeight: 'bold',
    color: '#333',
  },
  '.mainTitle:visited': {
    color: '#333',
  },
  '.subTitle': {
    color: '#5E6C84',
  },
  '.subTitle span': {
    color: '#666',
    fontSize: '12px',
  },
  '.hover .acms-admin-label': {
    backgroundColor: '#FFFFFF',
    color: '#1861D8 !important',
  },
  '.hover .mainTitle': {
    color: '#333',
  },
  '.hover .subTitle': {
    color: '#5E6C84',
  },
  '.hover .subTitle span': {
    color: '#666',
  },
  '.acms-admin-table-admin td': {
    display: 'table-cell',
    border: 'none',
    transition: 'auto',
    backgroundColor: '#fff',
    padding: '8px 5px',
    lineHeight: '1.3',
  },
  '.acms-admin-modal-footer': {
    borderTop: '1px solid #CCC',
    margin: '0 -5px',
    padding: '10px 0 0 0',
  },
  '.acms-admin-list-inline': {
    color: '#666',
    fontSize: '12px',
    textAlign: 'right',
    margin: '5px 0',
  },
  '.acms-admin-list-inline li': {
    paddingRight: '9px',
    fontSize: '12px !important',
  },
  '.acms-admin-list-inline li strong': {
    background: '#eee',
    padding: '0 5px',
    borderRadius: '2px',
  },
  '.initial-mark': {
    width: '30px',
    height: '30px',
    marginLeft: '5px',
    marginRight: '5px',
    borderRadius: '50px',
    backgroundColor: '#aaa',
    position: 'relative',
    textAlign: 'center',
  },
  '.initial-mark:after': {
    display: 'inline-block',
    marginTop: '4px',
    marginLeft: '1px',
    content: '',
    color: '#FFF',
    fontSize: '17px',
  },
  '.initial-b': {
    backgroundColor: '#d2b8b8',
  },
  '.initial-b:after': {
    content: "'B'",
  },
  '.initial-c': {
    backgroundColor: '#b8d2b8',
  },
  '.initial-c:after': {
    content: "'C'",
  },
  '.initial-e': {
    backgroundColor: '#b8ccd2',
  },
  '.initial-e:after': {
    content: "'E'",
  },
  '.initial-m': {
    backgroundColor: '#c4b8d2',
  },
  '.initial-m:after': {
    content: "'M'",
  },
  '.initial-m2': {
    backgroundColor: '#9fbf91',
  },
  '.initial-m2:after': {
    content: "'M'",
  },
  '.initial-v': {
    backgroundColor: '#b8d2c9',
  },
  '.initial-v:after': {
    content: "'V'",
  },
  '.initial-s': {
    backgroundColor: '#b6c1da',
  },
  '.initial-s:after': {
    content: "'S'",
  },
  '.initial-g': {
    backgroundColor: '#adadad',
  },
  '.initial-g:after': {
    content: "'G'",
  },
  '.customFieldCopied': {
    position: 'fixed',
    top: '-50px',
    bottom: 'auto',
    left: 0,
    right: 0,
    fontSize: '13px',
    borderRadius: 0,
    background: '#5690d8',
    color: '#fff',
    padding: '10px',
    width: '100%',
    zIndex: 2501,
    transition: 'top .2s ease-in',
  },
  '.customFieldCopied.active': {
    top: 0,
  },
})

const styleElement = Style.getStyles()

export default class QuickSearch extends Component {
  constructor(props) {
    super(props)
    this.state = {
      lists: [],
      init: false,
      menus: null,
      snippets: null,
      vars: null,
      globalVars: null,
      number: -1,
      display: 'none',
      keyword: '',
      isMacOs: navigator.userAgent.match(/Mac|PPC/),
      showModal: false,
      modalContent: '',
      copied: false,
    }
    this.currentItem = null
    this.box = null

    this.handleCloseModal = this.handleCloseModal.bind(this)
    this.handleCopySnippet = this.handleCopySnippet.bind(this)
  }

  componentDidUpdate() {
    if (this.currentItem) {
      const boxTop = this.box.getBoundingClientRect().top
      const boxBottom = boxTop + this.box.offsetHeight
      const itemTop = this.currentItem.getBoundingClientRect().top
      const itemBottom = itemTop + this.currentItem.offsetHeight
      const positionTop = itemTop - boxTop
      const positionBottom = boxBottom - itemBottom
      if (positionTop < 0 || positionBottom < 0) {
        this.box.scrollTop += positionTop
      }
    }
  }

  componentDidMount() {
    const is = new IncrementalSearch()

    is.addRequest(
      this.search,
      ACMS.Library.acmsLink({
        bid: ACMS.Config.bid,
      }),
      (lists) => {
        this.setState({ lists })
      },
    )

    const items = document.querySelectorAll('.js-search-everything')
    if (items && items.length) {
      ;[].forEach.call(items, (item) => {
        item.addEventListener('click', () => {
          this.toggleDialog()
        })
      })
    }
    keyboardJS.bind(ACMS.Config.quickSearchCommand, (e) => {
      e.preventDefault()
      this.toggleDialog()
    })

    keyboardJS.bind(['escape'], () => {
      if (this.state.display === 'block') {
        this.setState({
          display: 'none',
          showModal: false,
          keyword: '',
          lists: [],
        })
        this.search.focus()
      }
    })

    keyboardJS.bind(['tab', 'down'], (e) => {
      if (this.state.display === 'block' && !this.state.showModal) {
        e.preventDefault()
        this.gotoNextItem()
      }
    })

    keyboardJS.bind(['shift + tab', 'up'], (e) => {
      if (this.state.display === 'block' && !this.state.showModal) {
        e.preventDefault()
        this.gotoPrevItem()
      }
    })

    keyboardJS.bind(['enter'], (e) => {
      if (this.state.display === 'block' && !this.state.showModal) {
        e.preventDefault()
        const item = this.getCurrentItem()
        this.handleClickEvent(item)
      }
    })
  }

  setDefinedLists() {
    const endpoint = `${ACMS.Library.acmsLink({
      tpl: 'acms-code/all.json',
    })}?cache=${new Date().getTime()}`

    const key = `acms_big_${ACMS.Config.bid}_quick_search_data`
    const storage = new ExpireLocalStorage()
    const data = storage.load(key)
    if (data) {
      this.setState({
        menus: data.menus,
        snippets: data.snippets,
        vars: data.vars,
      })
      this.setGlobalVars()
    } else {
      axiosLib
        .get(endpoint)
        .then((res) => {
          this.setState({
            menus: res.data.menus,
            snippets: res.data.snippets,
            vars: res.data.vars,
          })
          storage.save(key, res.data, 1800)
        })
        .then(() => {
          this.setGlobalVars()
        })
    }
  }

  setGlobalVars() {
    const params = new URLSearchParams()
    params.append('ACMS_POST_Search_GlobalVars', true)
    params.append('formToken', window.csrfToken)
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
      })
      this.setState({
        globalVars: res.data,
      })
    })
  }

  handleClickEvent(item) {
    const mode = this.getMode()
    if (mode === 'snippets') {
      this.showSnippets(item)
    } else if (mode === 'vars') {
      this.showVars(item)
    } else if (mode === 'global-vars') {
      this.showGlobalVars(item)
    } else {
      this.gotoLink(item)
    }
  }

  gotoLink(item) {
    if (item) {
      location.href = item.url
    }
  }

  showSnippets(item) {
    if (item) {
      axiosLib.get(item.url).then((res) => {
        const parser = new DOMParser()
        let html = parser.parseFromString(res.data, 'text/html')
        html = html.querySelector('textarea').innerHTML
        html = unescape(html)
        this.setState({
          showModal: true,
          modalContent: html,
        })
      })
    }
  }

  showVars(item) {
    if (item) {
      axiosLib.get(item.url).then((res) => {
        const parser = new DOMParser()
        const html = parser.parseFromString(res.data, 'text/html')
        this.setState({
          showModal: true,
          modalContent: html.body.innerHTML,
        })
      })
    }
  }

  showGlobalVars(item) {
    if (item) {
      copy(item.title)
      this.setState({
        copied: true,
      })
    }
  }

  getMode() {
    const { keyword } = this.state
    if (ACMS.Config.auth !== 'administrator') {
      return 'normal'
    }
    if (keyword.slice(0, 1) === ';') {
      return 'snippets'
    }
    if (keyword.slice(0, 1) === ':') {
      return 'vars'
    }
    if (keyword.slice(0, 1) === '%') {
      return 'global-vars'
    }
    return 'normal'
  }

  getFilteredSnippets() {
    const { keyword, snippets } = this.state
    const searchWord = keyword ? keyword.toLowerCase().slice(1) : ''

    if (snippets && snippets.items && searchWord) {
      const items = snippets.items.filter((item) => {
        if (
          item.title.toLowerCase().indexOf(searchWord) !== -1 ||
          item.subtitle.toLowerCase().indexOf(searchWord) !== -1
        ) {
          return true
        }
        return false
      })
      return {
        title: snippets.title,
        enTitle: snippets.enTitle,
        items,
      }
    }
    return snippets
  }

  getFilteredVars() {
    const { keyword, vars } = this.state
    const searchWord = keyword ? keyword.toLowerCase().slice(1) : ''

    if (vars && vars.items && searchWord) {
      const items = vars.items.filter((item) => {
        if (
          item.title.toLowerCase().indexOf(searchWord) !== -1 ||
          item.subtitle.toLowerCase().indexOf(searchWord) !== -1
        ) {
          return true
        }
        return false
      })
      return {
        title: vars.title,
        enTitle: vars.enTitle,
        items,
      }
    }
    return vars
  }

  getFilteredGlobalVars() {
    const { keyword, globalVars } = this.state
    const searchWord = keyword ? keyword.toLowerCase().slice(1) : ''

    if (globalVars && globalVars.items && searchWord) {
      const items = globalVars.items.filter((item) => {
        if (
          item.title.toLowerCase().indexOf(searchWord) !== -1 ||
          item.subtitle.toLowerCase().indexOf(searchWord) !== -1
        ) {
          return true
        }
        return false
      })
      return {
        title: globalVars.title,
        enTitle: globalVars.enTitle,
        items,
      }
    }
    return globalVars
  }

  getFilteredMenus() {
    const { keyword, menus } = this.state
    if (menus && menus.items) {
      const items = menus.items.filter((item) => {
        if (!keyword) {
          return false
        }
        if (
          item.title.indexOf(keyword) !== -1 ||
          item.subtitle.indexOf(keyword) !== -1
        ) {
          return true
        }
        return false
      })
      return {
        title: menus.title,
        enTitle: menus.enTitle,
        items,
      }
    }
    return menus
  }

  getCombindLists() {
    const { lists } = this.state
    const mode = this.getMode()
    if (mode === 'snippets') {
      return [this.getFilteredSnippets()]
    }
    if (mode === 'vars') {
      return [this.getFilteredVars()]
    }
    if (mode === 'global-vars') {
      return [this.getFilteredGlobalVars()]
    }
    const menus = this.getFilteredMenus()
    if (menus) {
      return [menus, ...lists]
    }
    return lists
  }

  setKeyword(keyword) {
    this.setState({
      keyword,
      number: 0,
    })
  }

  toggleDialog() {
    const { display, init } = this.state
    if (init === false) {
      this.setState({
        init: true,
      })
      this.setDefinedLists()
    }
    const nextStyle = display === 'block' ? 'none' : 'block'
    this.setState({
      display: nextStyle,
      keyword: '',
      lists: [],
    })
    if (nextStyle === 'block') {
      this.search.focus()
    }
  }

  gotoNextItem() {
    const { display, number } = this.state
    const lists = this.getCombindLists()
    const maxNumber = this.getNumber(lists.length, 0) - 1
    if (display === 'none') {
      return
    }
    const nextNumber = number + 1 > maxNumber ? 0 : number + 1
    this.setState({
      number: nextNumber,
    })
  }

  gotoPrevItem() {
    const { display, number } = this.state
    const lists = this.getCombindLists()
    const maxNumber = this.getNumber(lists.length, 0) - 1
    if (display === 'none') {
      return
    }
    const nextNumber = number - 1 < 0 ? maxNumber : number - 1
    this.setState({
      number: nextNumber,
    })
  }

  getCurrentItem() {
    const { number } = this.state
    const lists = this.getCombindLists()
    let itemNum = 0
    let res = false
    lists.forEach((list) => {
      list.items.forEach((item) => {
        if (number === itemNum) {
          res = item
        }
        itemNum++
      })
    })
    return res
  }

  setNumber(listIndex, index) {
    const number = this.getNumber(listIndex, index)
    this.setState({ number })
  }

  getNumber(listIndex, index) {
    const lists = this.getCombindLists()
    let number = 0
    while (listIndex > 0) {
      listIndex--
      if (lists[listIndex]) {
        if (lists[listIndex].items) {
          number += lists[listIndex].items.length
        }
      }
    }
    return number + index
  }

  handleCloseModal() {
    const { keyword } = this.state
    this.setState({
      showModal: false,
      keyword: keyword.replace(/^(:|;)(.*)/g, '$1'),
    })
    this.search.focus()
  }

  handleCopySnippet() {
    const { modalContent } = this.state
    copy(modalContent)
    this.setState({
      copied: true,
    })
  }

  getInitialClassByName(name) {
    switch (name) {
      case 'Blogs':
        return 'initial-b'
      case 'Categories':
        return 'initial-c'
      case 'Entries':
        return 'initial-e'
      case 'Modules':
        return 'initial-m'
      case 'Menu':
        return 'initial-m2'
      case 'Vars':
        return 'initial-v'
      case 'Snippets':
        return 'initial-s'
      case 'Global vars':
        return 'initial-g'
      default:
        return ''
    }
  }

  render() {
    const { display, number, isMacOs, showModal, modalContent, copied } =
      this.state
    const lists = this.getCombindLists()
    const mode = this.getMode()

    return (
      <div className={styleQuickSearch}>
        <div // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-noninteractive-element-interactions
          className="acms-admin-modal display acms-admin-modal-middle"
          style={{ display }}
          id="quick-search-dialog"
          onClick={(e) => {
            if (e.target.id === 'quick-search-dialog') {
              this.toggleDialog()
            }
          }}
          role="dialog"
        >
          <ACMSModal
            isOpen={showModal}
            onClose={this.handleCloseModal}
            title={
              mode === 'snippets' ? (
                <h3>{ACMS.i18n('quick_search.snippets')}</h3>
              ) : (
                <h3>{ACMS.i18n('quick_search.vars')}</h3>
              )
            }
            lastFocus={mode === 'snippets'}
            footer={
              <div style={{ marginRight: '20px' }}>
                <button
                  type="button"
                  onClick={this.handleCloseModal}
                  className="acms-admin-btn"
                >
                  {ACMS.i18n('quick_search.close')}
                </button>
                {mode === 'snippets' && (
                  <button
                    type="button"
                    onClick={this.handleCopySnippet}
                    className="acms-admin-btn"
                  >
                    {ACMS.i18n('quick_search.copy')}
                  </button>
                )}
              </div>
            }
          >
            <div className="acms-admin-form">
              <div style={{ paddingTop: '10px', paddingBottom: '10px' }}>
                {mode === 'snippets' ? (
                  <textarea
                    rows="10"
                    value={modalContent}
                    readOnly
                    style={{ width: '100%', fontSize: '16px' }}
                  />
                ) : (
                  // eslint-disable-next-line react/no-danger
                  <div
                    className={styleValiableTable}
                    dangerouslySetInnerHTML={{ __html: modalContent }}
                  />
                )}
              </div>
            </div>
          </ACMSModal>
          <div className="acms-admin-modal-dialog acms-admin-modal-quick-search">
            <div className="acms-admin-modal-content">
              <div className="acms-admin-modal-body">
                <div
                  className="acms-admin-form"
                  style={{ paddingTop: '15px', paddingBottom: '15px' }}
                >
                  <input
                    type="text"
                    ref={(ref) => {
                      this.search = ref
                    }}
                    style={{ fontSize: '24px', fontWeight: 'bold' }}
                    placeholder={ACMS.i18n('quick_search.input_placeholder')}
                    className="acms-admin-form-width-full acms-admin-form-large acms-admin-margin-bottom-small"
                    onInput={(e) => {
                      this.setKeyword(e.target.value)
                    }}
                  />
                </div>
                <div
                  ref={(element) => {
                    this.box = element
                  }}
                  className="acms-admin-modal-middle-scroll"
                >
                  {lists.map((list, listIndex) => (
                    <div key={`label-${list.enTitle}`}>
                      {list && list.items.length > 0 && (
                        <div>
                          <h2
                            className="acms-admin-admin-title3"
                            style={{ background: '#FFF' }}
                          >
                            {list.title}
                          </h2>
                          <table className="acms-admin-table-admin acms-admin-form acms-admin-table-hover">
                            <tbody key={`body-${list.enTitle}`}>
                              {list.items.map((item, index) => (
                                <tr
                                  key={this.getNumber(listIndex, index)}
                                  ref={(element) => {
                                    if (
                                      this.getNumber(listIndex, index) ===
                                      number
                                    )
                                      this.currentItem = element
                                  }}
                                  onClick={this.handleClickEvent.bind(
                                    this,
                                    item,
                                  )}
                                  onMouseMove={this.setNumber.bind(
                                    this,
                                    listIndex,
                                    index,
                                  )}
                                  className={classnames({
                                    hover:
                                      this.getNumber(listIndex, index) ===
                                      number,
                                  })}
                                >
                                  <td
                                    style={{
                                      width: '1px',
                                      wordBreak: 'break-all',
                                    }}
                                  >
                                    <div
                                      className={classnames(
                                        'initial-mark',
                                        this.getInitialClassByName(
                                          list.enTitle,
                                        ),
                                      )}
                                    />
                                  </td>
                                  <td>
                                    <a className="mainTitle" href={item.url}>
                                      {item.title}
                                    </a>
                                    <div className="subTitle">
                                      {item.subtitle}{' '}
                                      <span>{item.blogName}</span>
                                    </div>
                                  </td>
                                  {mode !== 'normal' ? (
                                    <td
                                      style={{
                                        textAlign: 'right',
                                        wordBreak: 'break-all',
                                      }}
                                    >
                                      {mode === 'global-vars' && (
                                        <span style={{ paddingRight: '10px' }}>
                                          {item.url}
                                        </span>
                                      )}
                                    </td>
                                  ) : (
                                    <td
                                      style={{
                                        width: '1px',
                                        textAlign: 'right',
                                        whiteSpace: 'nowrap',
                                      }}
                                    >
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
                <div className="acms-admin-modal-footer">
                  <ul className="acms-admin-list-inline">
                    <li>
                      <strong>tab</strong> or
                      <strong>⇅</strong> {ACMS.i18n('quick_search.choice')}
                    </li>
                    <li>
                      <strong>↵</strong> {ACMS.i18n('quick_search.move')}
                    </li>
                    <li>
                      <strong>esc</strong> {ACMS.i18n('quick_search.close')}
                    </li>
                    {ACMS.Config.auth === 'administrator' && (
                      <>
                        <li>
                          <strong>
                            {isMacOs ? <span>⌘K</span> : <span>ctl+k</span>}
                          </strong>{' '}
                          {ACMS.i18n('quick_search.open')}
                        </li>
                        <li>
                          <strong>;</strong>{' '}
                          {ACMS.i18n('quick_search.snippets')}
                        </li>
                        <li>
                          <strong>:</strong> {ACMS.i18n('quick_search.vars')}
                        </li>
                        <li>
                          <strong>%</strong> {ACMS.i18n('quick_search.g_vars')}
                        </li>
                      </>
                    )}
                  </ul>
                </div>
              </div>
            </div>
          </div>
          <style>{styleElement}</style>
        </div>
        <Notify
          message={ACMS.i18n('quick_search.copy_message')}
          show={copied}
          onFinish={() => {
            this.setState({
              copied: false,
            })
          }}
        />
      </div>
    )
  }
}
