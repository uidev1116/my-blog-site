import React from 'react'
import classnames from 'classnames'
import RangeSlider from 'rc-slider'
import dayjs from 'dayjs'
import * as FreeStyle from 'free-style'
import Select, { Creatable } from './react-select-styled'
import 'rc-slider/assets/index.css'
import 'react-select/dist/react-select.css'

import { MediaItem, MediaAjaxConfig } from '../types/media'
import axiosLib from '../lib/axios'
import { getFileName, formatBytes } from '../lib/utility'
import { parseQuery } from '../utils'
import { findAncestor } from '../lib/dom'
import * as actions from '../actions/media'
import Notify from './notify'
import Splash from './splash'
import ConditionalWrap from './conditional-wrap'

/* eslint jsx-a11y/label-has-associated-control: 0 */
/* eslint jsx-a11y/click-events-have-key-events: 0 */
/* eslint jsx-a11y/no-static-element-interactions: 0 */
/* eslint react/default-props-match-prop-types: 0 */
/* eslint camelcase: 0 */

interface MediaListProp {
  actions: typeof actions
  items: MediaItem[]
  archives: string[]
  tags: string[]
  extensions?: string[]
  selectedTags: string[]
  config: MediaAjaxConfig
  lastPage: number
  mode: string
  total: number
  filetype?: 'all' | 'image' | 'file'
  radioMode?: boolean
}

interface Label {
  value: string
  label: string
}

interface Menu {
  image: boolean
  id: boolean
  filename: boolean
  tag: boolean
  last_modified: boolean
  upload_datetime: boolean
  imagesize: boolean
  filesize: boolean
  blogname: boolean
}

interface OrderActive {
  media_title: string
  media_last_modified: string
  media_datetime?: string
  media_path: string
  media_size: string
  media_filesize?: string
}

interface MediaListState {
  order: OrderActive
  menu: Menu
  scale: number
  filterMenuOpened: boolean
  editMode: string
  year: string
  month: string
  toggleAll: boolean
  orderActive: keyof OrderActive
  search: string
  labels: Label[]
  selectedLabels: Label[]
  loading: boolean
  style: 'table' | 'list'
  tagAdded: boolean
  oldItemId: string | null
  items: MediaItem[]
  limit: string
  displayPage: number
  filteredOptions: Label[]
  dropdown: boolean
  orderDropdown: boolean
  fileext: string
  owner: boolean
  deleting: boolean
  filetype: 'all' | 'image' | 'file'
}

type FetchConfig = {
  date?: string
  order?: string
  tag?: string
  limit?: string
  page?: number
  year?: string
  month?: string
  filetype?: 'all' | 'image' | 'file'
  fileext?: string
  owner?: boolean
}

const delimiter = ','
const Style = FreeStyle.create()
const mediaListStyle = Style.registerStyle({
  '.acms-admin-inline-block': {
    verticalAlign: 'middle',
  },
})

const styleElement = Style.getStyles()

export default class MediaList extends React.Component<
  MediaListProp,
  MediaListState
> {
  list: HTMLFormElement

  static defaultProps = {
    radioMode: false,
    filetype: 'all',
    extensions: [],
  }

  constructor(props) {
    super(props)
    this.state = {
      order: {
        media_title: 'asc',
        media_last_modified: 'desc',
        media_path: 'asc',
        media_size: 'asc',
      },
      menu: {
        image: true,
        id: true,
        filename: true,
        tag: true,
        last_modified: true,
        upload_datetime: false,
        imagesize: true,
        filesize: true,
        blogname: false,
      },
      limit: '20',
      displayPage: 6,
      scale: 1,
      selectedLabels: [],
      year: '',
      month: '',
      filterMenuOpened: false,
      toggleAll: false,
      orderActive: 'media_last_modified',
      search: '',
      labels: [],
      items: [],
      filteredOptions: [],
      loading: false,
      deleting: false,
      style: 'table',
      editMode: '',
      filetype: props.filetype ? props.filetype : 'all',
      fileext: 'all',
      owner: false,
      tagAdded: false,
      oldItemId: null,
      dropdown: false,
      orderDropdown: false,
    }
  }

  getOrderSelectBtnLabel() {
    const { orderActive, order } = this.state
    const active =
      order[orderActive] === 'asc'
        ? ACMS.i18n('media.asc')
        : ACMS.i18n('media.desc')
    if (orderActive === 'media_title') {
      return `${ACMS.i18n('media.title')}（${active}）`
    }
    if (orderActive === 'media_last_modified') {
      return `${ACMS.i18n('media.update_date')}（${active}）`
    }
    if (orderActive === 'media_datetime') {
      return `${ACMS.i18n('media.created_date')}（${active}）`
    }
    if (orderActive === 'media_path') {
      return `${ACMS.i18n('media.file_name')}（${active}）`
    }
    if (orderActive === 'media_filesize') {
      return `${ACMS.i18n('media.file_size')}（${active}）`
    }
    return ACMS.i18n('media.select')
  }

  setItem(item) {
    const { media_id } = item
    const url = ACMS.Library.acmsLink(
      {
        tpl: 'ajax/edit/media-edit.json',
        bid: ACMS.Config.bid,
        Query: {
          cache: new Date().getTime(),
        },
      },
      false,
    )
    if ('history' in window) {
      history.replaceState(null, null, `#mid=${media_id}`)
    }
    axiosLib
      .get(url, {
        params: {
          _mid: media_id,
        },
      })
      .then((res) => {
        if (res.data.item && res.data.item.media_id) {
          this.props.actions.setItem(res.data.item)
        }
      })
    return false
  }

  toggleCheck(item) {
    const { items, radioMode } = this.props
    if (radioMode) {
      const findItem = items.find((item) => item.checked)
      if (findItem) {
        this.props.actions.updateMediaList({
          ...findItem,
          checked: !findItem.checked,
        })
      }
    }
    this.props.actions.updateMediaList({ ...item, checked: !item.checked })
  }

  toggleCheckFrom(fromItemId: string, toItem: MediaItem) {
    const { items } = this.props
    const { updateMediaList, radioMode } = this.props.actions
    if (radioMode) {
      return
    }
    const fromIndex = items.findIndex((item) => {
      if (item.media_id === fromItemId) {
        return true
      }
      return false
    })
    const toIndex = items.findIndex((item) => {
      if (item.media_id === toItem.media_id) {
        return true
      }
      return false
    })
    const { checked } = toItem
    const nextChecked = !checked
    const minIndex = fromIndex > toIndex ? toIndex : fromIndex
    const maxIndex = fromIndex > toIndex ? fromIndex : toIndex
    items.forEach((item, index) => {
      if (index >= minIndex && index <= maxIndex) {
        updateMediaList({ ...item, checked: nextChecked })
      }
    })
  }

  toggleAllCheck() {
    const { items, actions, radioMode } = this.props
    const { toggleAll } = this.state
    const all = !toggleAll
    this.setState({
      toggleAll: all,
    })
    if (radioMode) {
      return
    }
    if (all) {
      actions.setMediaList(items.map((item) => ({ ...item, checked: true })))
    } else {
      actions.setMediaList(items.map((item) => ({ ...item, checked: false })))
    }
  }

  removeImgs() {
    if (!confirm(ACMS.i18n('media.remove_media_confirm'))) {
      return
    }
    const fd = new FormData(this.list)
    fd.append('ACMS_POST_Media_Index_Delete', 'true')
    fd.append('formToken', window.csrfToken)
    this.setState({
      deleting: true,
    })
    $.ajax({
      url: ACMS.Library.acmsLink({ bid: ACMS.Config.bid }),
      type: 'POST',
      data: fd,
      processData: false,
      contentType: false,
    }).then(() => {
      this.fetchMediaList()
      this.setState({
        deleting: false,
      })
    })
  }

  setOrder(target) {
    const { state } = this
    const { order } = state
    // eslint-disable-next-line no-unused-expressions
    order[target] === 'asc' ? (order[target] = 'desc') : (order[target] = 'asc')
    this.setState(
      {
        ...state,
        orderActive: target,
        order,
      },
      () => {
        this.fetchMediaList()
      },
    )
    // upload_date-desc
  }

  checkOrder(orderActive: keyof OrderActive) {
    this.setState(
      {
        orderActive,
      },
      () => {
        this.fetchMediaList()
      },
    )
  }

  checkAscDesc(ascdesc: 'asc' | 'desc') {
    const { order, orderActive } = this.state
    order[orderActive] = ascdesc
    this.setState(
      {
        order,
      },
      () => {
        this.fetchMediaList()
      },
    )
  }

  getOrder() {
    const { orderActive, order } = this.state
    if (orderActive === 'media_title') {
      return `file_name-${order[orderActive]}`
    }
    if (orderActive === 'media_last_modified') {
      return `last_modified-${order[orderActive]}`
    }
    if (orderActive === 'media_datetime') {
      return `upload_date-${order[orderActive]}`
    }
    if (orderActive === 'media_filesize') {
      return `file_size-${order[orderActive]}`
    }
    return ''
  }

  fetchMediaList(config: FetchConfig = {}) {
    const { year, month, limit, filetype, fileext, owner } = this.state
    const { actions, selectedTags: labels } = this.props
    const order = this.getOrder()
    const tag = labels.reduce((accumulator, current) => {
      if (!accumulator) {
        return current
      }
      return `${accumulator}/${current}`
    }, '')
    const override: FetchConfig = {
      order,
      tag,
      limit,
      filetype,
      fileext,
      owner,
    }
    if (year && month) {
      override.date = `${year}/${month}`
    } else {
      override.date = ''
      override.year = year
      override.month = month
    }
    if (!config.page) {
      config.page = 1
    }
    const settings = { ...config, ...override }
    if (actions && actions.fetchMediaList) {
      actions.fetchMediaList(settings)
    }
  }

  getThClassName(th) {
    const { order, orderActive } = this.state
    if (orderActive === th) {
      if (order[orderActive] === 'asc') {
        return '-asc'
      }
      return '-desc'
    }
    return ''
  }

  changeStyle(style) {
    this.setState({
      style,
    })
  }

  getCheckedItemsLength() {
    const { items } = this.props
    const checks = items.filter((item) => item.checked)
    if (checks && checks.length) {
      return checks.length
    }
    return 0
  }

  onLabelChange(labels: Label[]) {
    const tags = labels.map((label) => label.value)
    this.props.actions.setSelectedTags(tags)
    this.getFilteredOptions(tags)
  }

  getFilteredOptions(tags: string[]) {
    axiosLib({
      method: 'GET',
      url: ACMS.Library.acmsLink(
        {
          tpl: 'ajax/edit/media-tag.json',
          bid: ACMS.Config.bid,
          Query: {
            cache: new Date().getTime(),
          },
        },
        false,
      ),
      responseType: 'json',
      params: {
        tags,
      },
    }).then((response) => {
      const { data } = response
      const filteredOptions = data.map((item) => ({
        value: item,
        label: item,
      }))
      this.setState({
        filteredOptions,
      })
    })
  }

  onChangeDisplayNumber(e) {
    const { actions } = this.props
    const { value: limit } = e.target
    actions.setMediaConfig({
      limit,
      page: 1,
    })
    this.setState({ limit })
  }

  onKeywordInput(e) {
    const { actions } = this.props
    actions.setMediaConfig({
      keyword: e.target.value,
    })
  }

  getArchives(archives) {
    let years = []
    let months = []
    archives.forEach((archive) => {
      const [year, month] = archive.split('-')
      years.push(year)
      months.push(month)
    })
    years = [...new Set(years)].sort()
    months = [...new Set(months)].sort()
    return { years, months }
  }

  onDateChange(value, type: 'year' | 'month') {
    if (type === 'month') {
      this.setState({
        month: value,
      })
    } else {
      this.setState({
        year: value,
      })
    }
  }

  onAddedLabelsChange(labels) {
    this.setState({
      selectedLabels: labels,
    })
  }

  async addLabelToItems() {
    const { selectedLabels } = this.state
    const tags = selectedLabels
      .map((selectedLabel) => selectedLabel.value)
      .join(',')
    this.setState({
      loading: true,
    })
    const fd = new FormData(this.list)
    fd.append('tags', tags)
    fd.append('ACMS_POST_Media_Index_Tags', 'true')
    fd.append('formToken', window.csrfToken)
    await $.ajax({
      url: ACMS.Library.acmsLink({ bid: ACMS.Config.bid }),
      data: fd,
      type: 'POST',
      processData: false,
      contentType: false,
    })
    this.setState({
      loading: false,
      tagAdded: true,
    })
    this.fetchMediaList()
  }

  getFileStyle = (item: MediaItem): React.CSSProperties => {
    if (!item) {
      return {}
    }
    if (item.media_ext.toLowerCase() === 'pdf') {
      return {
        height: '70%',
        width: 'auto',
        opacity: 1,
      }
    }
    return {
      verticalAlign: 'middle',
      width: '70px',
      opacity: 1,
    }
  }

  getImageStyle = (item: MediaItem): React.CSSProperties => {
    if (!item || !item.width) {
      return {}
    }
    if (item.width > item.height) {
      return {
        width: '100%',
        height: 'auto',
        opacity: 1,
        transform: `translate(-50%,-50%) scale(${item.width / item.height})`,
      }
    }
    return {
      width: 'auto',
      height: '100%',
      opacity: 1,
      transform: `translate(-50%,-50%) scale(${item.height / item.width})`,
    }
  }

  rowClick(e, item: MediaItem) {
    const { oldItemId } = this.state
    const element = e.target as HTMLElement
    if (
      element.tagName === 'INPUT' ||
      element.tagName === 'I' ||
      element.tagName === 'LABEL'
    ) {
      e.preventDefault()
    }
    if (element.tagName !== 'BUTTON') {
      if (e.shiftKey) {
        this.toggleCheckFrom(oldItemId, item)
      } else {
        this.toggleCheck(item)
        this.setState({
          oldItemId: item.media_id,
        })
      }
    }
  }

  UNSAFE_componentWillReceiveProps(props) {
    if (props.items) {
      this.setItems(props.items)
    }
  }

  componentDidUpdate() {
    const { state } = this
    const { style, scale, order, orderActive, limit, filterMenuOpened, owner } =
      state
    localStorage.setItem(
      'acms-media-list',
      JSON.stringify({
        style,
        scale,
        order,
        orderActive,
        limit,
        filterMenuOpened,
        owner,
      }),
    )
  }

  componentDidMount() {
    const storage = localStorage.getItem('acms-media-list')
    const { selectedTags } = this.props
    if (!storage) {
      this.fetchMediaList()
      return
    }
    if (location.hash) {
      const { mid } = parseQuery(location.hash.slice(1))
      if (mid) {
        this.setItem({ media_id: mid })
      }
    }
    const { style, scale, order, orderActive, limit, filterMenuOpened, owner } =
      JSON.parse(storage)
    this.setState(
      {
        style,
        scale,
        order,
        orderActive,
        limit,
        filterMenuOpened,
        owner,
      },
      () => {
        if (this.props.items && this.props.items.length) {
          this.setItems(this.props.items)
        } else {
          this.fetchMediaList()
        }
      },
    )
    if (selectedTags) {
      this.getFilteredOptions(selectedTags)
    }
  }

  loadImg(url: string): Promise<HTMLImageElement> {
    return new Promise((resolve) => {
      const img = new Image()
      img.onload = () => {
        resolve(img)
      }
      setTimeout(() => {
        resolve(img)
      }, 500)
      img.src = url
    })
  }

  setItems(items: MediaItem[]) {
    const newItems = items.map(async (item) => {
      if (
        (item.media_type === 'image' || item.media_type === 'svg') &&
        !item.width
      ) {
        const img = await this.loadImg(item.media_thumbnail)
        if (img.naturalWidth && img.naturalHeight) {
          return { ...item, width: img.naturalWidth, height: img.naturalHeight }
        }
        return { ...item, width: img.width, height: img.height }
      }
      return item
    })
    Promise.all(newItems).then((items) => {
      this.setState({ items })
    })
  }

  scaleChanged = (scale) => {
    this.setState({ scale })
  }

  getOptions = () => {
    const { filteredOptions } = this.state
    const { tags, selectedTags } = this.props
    if (selectedTags.length) {
      return filteredOptions
    }
    return tags.map((tag) => ({
      value: tag,
      label: tag,
    }))
  }

  showDropdownMenu = () => {
    this.setState(
      {
        dropdown: true,
      },
      () => {
        document.addEventListener('click', this.closeDropdownMenu)
      },
    )
  }

  closeDropdownMenu = (e) => {
    if (findAncestor(e.target, '.acms-admin-dropdown-menu')) {
      return
    }
    this.setState(
      {
        dropdown: false,
      },
      () => {
        document.removeEventListener('click', this.closeDropdownMenu)
      },
    )
  }

  showOrderDropdown = () => {
    this.setState(
      {
        orderDropdown: true,
      },
      () => {
        document.addEventListener('click', this.closeOrderDropdown)
      },
    )
  }

  closeOrderDropdown = (e) => {
    if (findAncestor(e.target, '.acms-admin-dropdown-menu')) {
      return
    }
    this.setState(
      {
        orderDropdown: false,
      },
      () => {
        document.removeEventListener('click', this.closeOrderDropdown)
      },
    )
  }

  toggleMenu(menuItem: keyof Menu) {
    const { menu } = this.state
    const newMenu = { ...menu, [menuItem]: !menu[menuItem] }
    this.setState({
      menu: newMenu,
    })
  }

  startPage() {
    return this.props.config.page - this.state.displayPage > 0
      ? this.props.config.page - this.state.displayPage
      : 1
  }

  endPage() {
    return this.props.config.page + this.state.displayPage < this.props.lastPage
      ? this.props.config.page + this.state.displayPage
      : this.props.lastPage
  }

  pages() {
    return [...Array(this.endPage() + 1).keys()].slice(this.startPage())
  }

  render() {
    const {
      mode,
      lastPage,
      config,
      archives,
      tags,
      total,
      items,
      selectedTags,
      extensions,
    } = this.props

    const { years, months } = this.getArchives(archives)

    const { page, keyword } = config
    const {
      style,
      deleting,
      editMode,
      scale,
      dropdown,
      orderDropdown,
      selectedLabels,
      tagAdded,
      items: stateItems,
      limit,
      menu,
      owner,
      order,
      orderActive,
      oldItemId,
      toggleAll,
      filterMenuOpened,
      filetype,
    } = this.state

    const checkedLength = this.getCheckedItemsLength()

    return (
      <div className={mediaListStyle}>
        <style>{styleElement}</style>
        <div className="acms-admin-filter">
          <div className="acms-admin-form">
            <div className="acms-admin-filter-body">
              <div className="acms-admin-filter-group">
                <p className="acms-admin-filter-label">
                  {ACMS.i18n('media.filter_search')}
                </p>

                <div className="acms-admin-filter-content acms-admin-filter-content-fit">
                  <div className="acms-admin-filter-item">
                    <label
                      className="acms-admin-filter-item-name"
                      htmlFor="filter-date"
                    >
                      {ACMS.i18n('media.created_date')}：
                    </label>

                    <div className="acms-admin-filter-just">
                      <div className="acms-admin-filter-just-item acms-admin-margin-right-mini">
                        <select
                          id="filter-date"
                          className="acms-admin-margin-right-mini"
                          onChange={(e) => {
                            this.onDateChange(e.target.value, 'year')
                          }}
                        >
                          <option value="">{ACMS.i18n('media.year')}</option>
                          {years.map((year) => (
                            <option value={year} key={year}>
                              {year}
                            </option>
                          ))}
                        </select>
                      </div>
                      <div className="acms-admin-filter-just-item">
                        <select
                          onChange={(e) => {
                            this.onDateChange(e.target.value, 'month')
                          }}
                        >
                          <option value="">{ACMS.i18n('media.month')}</option>
                          {months.map((month) => (
                            <option value={month} key={month}>
                              {month}
                            </option>
                          ))}
                        </select>
                      </div>
                    </div>
                  </div>

                  <div className="acms-admin-filter-item acms-admin-filter-item-full">
                    <label
                      htmlFor="filter-tag"
                      className="acms-admin-filter-item-name"
                    >
                      {ACMS.i18n('media.tag')}
                    </label>
                    <div className="acms-admin-form-width-medium">
                      <Select
                        multi
                        value={selectedTags.map((selectedTag) => ({
                          label: selectedTag,
                          value: selectedTag,
                        }))}
                        options={this.getOptions()}
                        className="acms-admin-select2"
                        onChange={this.onLabelChange.bind(this)}
                        id="filter-tag"
                      />
                    </div>
                  </div>
                </div>
              </div>

              {filterMenuOpened && (
                <div id="search-detail">
                  <div className="acms-admin-filter-inner">
                    <div className="acms-admin-filter-group">
                      <p className="acms-admin-filter-label">
                        {ACMS.i18n('media.detailed_condition')}
                      </p>
                      <div className="acms-admin-filter-content">
                        <div className="acms-admin-filter-item acms-admin-filter-item-full">
                          <label
                            className="acms-admin-filter-item-name"
                            htmlFor="filter-file-name"
                          >
                            {ACMS.i18n('media.file_name')}
                          </label>
                          <input
                            type="text"
                            placeholder={ACMS.i18n('media.search_by_filename')}
                            id="filter-file-name"
                            className="acms-admin-form-width-full"
                            value={keyword}
                            onInput={this.onKeywordInput.bind(this)}
                          />
                        </div>
                      </div>
                    </div>
                    <div className="acms-admin-filter-group">
                      <p className="acms-admin-filter-label">
                        {ACMS.i18n('media.narrow_down')}
                      </p>
                      <div className="acms-admin-filter-content">
                        <div className="acms-admin-filter-item">
                          <label
                            className="acms-admin-filter-item-name"
                            htmlFor="filter-sort"
                          >
                            {ACMS.i18n('media.number_of_items')}
                          </label>
                          <select
                            id="filter-sort"
                            value={limit}
                            onChange={this.onChangeDisplayNumber.bind(this)}
                          >
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="75">75</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="300">300</option>
                          </select>
                        </div>
                        <div className="acms-admin-filter-item">
                          <label
                            className="acms-admin-filter-item-name"
                            htmlFor="filter-type"
                          >
                            {ACMS.i18n('media.type')}
                          </label>
                          <select
                            id="filter-type"
                            disabled={this.props.filetype !== 'all'}
                            defaultValue={filetype}
                            onChange={(e) => {
                              this.setState({
                                filetype: e.target.value,
                              })
                            }}
                          >
                            <option value="all">
                              {ACMS.i18n('media.all')}
                            </option>
                            <option value="image">
                              {ACMS.i18n('media.image')}
                            </option>
                            <option value="file">
                              {ACMS.i18n('media.file')}
                            </option>
                          </select>
                        </div>
                        <div className="acms-admin-filter-item">
                          <label
                            htmlFor="filter-ext"
                            className="acms-admin-filter-item-name"
                          >
                            {ACMS.i18n('media.extension')}
                          </label>
                          <select
                            id="filter-ext"
                            onChange={(e) => {
                              this.setState({
                                fileext: e.target.value,
                              })
                            }}
                          >
                            <option value="all">
                              {ACMS.i18n('media.all')}
                            </option>
                            {extensions.map((extension) => (
                              <option
                                value={extension}
                                key={extension.toLowerCase()}
                              >
                                {extension.toLowerCase()}
                              </option>
                            ))}
                          </select>
                        </div>
                        <div className="acms-admin-filter-item">
                          <label
                            htmlFor="filter-owner"
                            className="acms-admin-form-checkbox acms-admin-margin-none"
                          >
                            <input
                              id="filter-owner"
                              type="checkbox"
                              checked={owner}
                              onChange={(e) => {
                                this.setState({
                                  owner: e.target.checked,
                                })
                              }}
                            />
                            <i className="acms-admin-ico-checkbox" />
                            {ACMS.i18n('media.owner')}
                          </label>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
          <div className="acms-admin-filter-footer">
            <button
              type="button"
              className="acms-admin-btn acms-admin-btn-info acms-admin-btn-search"
              onClick={() => {
                this.fetchMediaList()
              }}
            >
              {ACMS.i18n('media.search')}
            </button>

            <p className="acms-admin-filter-detail-btn">
              <a
                href="#search-detail"
                onClick={(e) => {
                  e.preventDefault()
                  this.setState({
                    filterMenuOpened: !filterMenuOpened,
                  })
                }}
              >
                <span className="acms-admin-icon-arrow-right" />
                {ACMS.i18n('media.detailed_search')}
              </a>
            </p>
          </div>
        </div>
        <div className="acms-admin-media-action-group">
          <div className="acms-admin-media-action-item acms-admin-media-action-item-fix">
            <div
              className="acms-admin-btn-group acms-admin-media-action-display-switch"
              style={{ padding: '0px' }}
            >
              <button
                type="button"
                className={classnames('acms-admin-btn', {
                  'acms-admin-btn-active': style === 'table',
                })}
                onClick={this.changeStyle.bind(this, 'table')}
                disabled={style === 'table'}
              >
                <i className="acms-admin-icon-list" />
              </button>
              <button
                type="button"
                className={classnames('acms-admin-btn', {
                  'acms-admin-btn-active': style === 'list',
                })}
                onClick={this.changeStyle.bind(this, 'list')}
                disabled={style === 'list'}
              >
                <i className="acms-admin-icon-grid" />
              </button>
            </div>

            <div className="acms-admin-media-action-sort-select-wrap">
              <div className="acms-admin-media-action-sort-select">
                {style === 'table' && (
                  <button
                    type="button"
                    className="acms-admin-btn"
                    onClick={this.showDropdownMenu}
                  >
                    <i className="acms-admin-icon-config" />
                  </button>
                )}
                {dropdown && (
                  <ul
                    className="acms-admin-dropdown-menu"
                    style={{ display: 'block', minWidth: '160px' }}
                  >
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('image')
                          }}
                          checked={menu.image}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.image2')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('id')
                          }}
                          checked={menu.id}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        ID
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('filename')
                          }}
                          checked={menu.filename}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.file_name')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('tag')
                          }}
                          checked={menu.tag}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.tag')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('last_modified')
                          }}
                          checked={menu.last_modified}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.updated_date')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('upload_datetime')
                          }}
                          checked={menu.upload_datetime}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.created_date')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('filesize')
                          }}
                          checked={menu.filesize}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.file_size')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-checkbox">
                        <input
                          type="checkbox"
                          onChange={() => {
                            this.toggleMenu('blogname')
                          }}
                          checked={menu.blogname}
                        />
                        <i className="acms-admin-ico-checkbox" />
                        {ACMS.i18n('media.blog_name')}
                      </label>
                    </li>
                  </ul>
                )}
              </div>

              <div
                className="acms-admin-media-action-sort-select"
                style={{ position: 'relative' }}
              >
                <button
                  type="button"
                  className="acms-admin-select-btn"
                  onClick={this.showOrderDropdown.bind(this)}
                >
                  {this.getOrderSelectBtnLabel()}
                </button>
                {orderDropdown && (
                  <ul
                    className="acms-admin-dropdown-menu"
                    style={{ display: 'block', minWidth: '160px' }}
                  >
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-radio">
                        <input
                          type="radio"
                          checked={orderActive === 'media_title'}
                          onChange={() => {
                            this.checkOrder('media_title')
                          }}
                        />
                        <i className="acms-admin-ico-radio" />
                        {ACMS.i18n('media.title')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-radio">
                        <input
                          type="radio"
                          checked={orderActive === 'media_last_modified'}
                          onChange={() => {
                            this.checkOrder('media_last_modified')
                          }}
                        />
                        <i className="acms-admin-ico-radio" />
                        {ACMS.i18n('media.updated_date')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-radio">
                        <input
                          type="radio"
                          checked={orderActive === 'media_datetime'}
                          onChange={() => {
                            this.checkOrder('media_datetime')
                          }}
                        />
                        <i className="acms-admin-ico-radio" />
                        {ACMS.i18n('media.created_date')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-radio">
                        <input
                          type="radio"
                          checked={orderActive === 'media_filesize'}
                          onChange={() => {
                            this.checkOrder('media_filesize')
                          }}
                        />
                        <i className="acms-admin-ico-radio" />
                        {ACMS.i18n('media.file_size')}
                      </label>
                    </li>
                    <li style={{ padding: '5px', borderTop: '1px solid #EEE' }}>
                      <label className="acms-admin-form-radio">
                        <input
                          type="radio"
                          checked={order[orderActive] === 'asc'}
                          onChange={() => {
                            this.checkAscDesc('asc')
                          }}
                        />
                        <i className="acms-admin-ico-radio" />
                        {ACMS.i18n('media.asc')}
                      </label>
                    </li>
                    <li style={{ padding: '5px' }}>
                      <label className="acms-admin-form-radio">
                        <input
                          type="radio"
                          checked={order[orderActive] === 'desc'}
                          onChange={() => {
                            this.checkAscDesc('desc')
                          }}
                        />
                        <i className="acms-admin-ico-radio" />
                        {ACMS.i18n('media.desc')}
                      </label>
                    </li>
                  </ul>
                )}
              </div>
            </div>
          </div>

          <div className="acms-admin-media-action-item acms-admin-media-action-item-middle">
            {style === 'list' && (
              <div className="acms-admin-media-action-range">
                <span
                  style={{
                    display: 'inline-flex',
                    width: '100%',
                    alignItems: 'center',
                  }}
                >
                  <span
                    style={{
                      display: 'block',
                      whiteSpace: 'nowrap',
                      marginRight: '10px',
                      fontSize: '13px',
                    }}
                    className="acms-admin-hide-visually"
                  >
                    {ACMS.i18n('media.display_size')}
                  </span>
                  <i
                    className="acms-admin-icon-unit-image"
                    style={{
                      fontSize: '12px',
                      color: '#777777',
                      marginRight: '10px',
                    }}
                  />
                  <RangeSlider
                    className="acms-admin-range-slider"
                    min={1}
                    max={2}
                    step={0.1}
                    value={scale}
                    onChange={this.scaleChanged}
                  />
                  <i
                    className="acms-admin-icon-unit-image"
                    style={{
                      fontSize: '18px',
                      color: '#777777',
                      marginLeft: '10px',
                    }}
                  />
                </span>
              </div>
            )}

            {style === 'list' && (
              <div className="acms-admin-media-action-select-all">
                <label
                  onChange={this.toggleAllCheck.bind(this)}
                  className="acms-admin-media-grid-checkbox-wrap"
                >
                  <div
                    className={classnames('acms-admin-media-grid-checkbox', {
                      selected: toggleAll,
                    })}
                  />
                  <input type="checkbox" />
                  {ACMS.i18n('media.select_all')}
                </label>
              </div>
            )}
          </div>

          <div className="acms-admin-form-group acms-admin-media-action-item acms-admin-media-action-item-left">
            <div className="acms-admin-form">
              <select
                className="acms-admin-margin-right-mini"
                value={editMode}
                onChange={(e) => {
                  const { value } = e.target
                  this.setState({
                    editMode: value,
                  })
                }}
              >
                <option>{ACMS.i18n('media.select')}</option>
                <option value="remove">{ACMS.i18n('media.remove')}</option>
                <option value="tag">{ACMS.i18n('media.add_tags')}</option>
              </select>
              {editMode === 'remove' && (
                <>
                  {ACMS.i18n('media.checked_items').replace(
                    '$1',
                    checkedLength,
                  )}
                  &nbsp;
                  <button
                    type="button"
                    className="acms-admin-btn acms-admin-btn-danger"
                    onClick={this.removeImgs.bind(this)}
                  >
                    {ACMS.i18n('media.remove')}
                  </button>
                </>
              )}
              {editMode === 'tag' && (
                <>
                  <div
                    style={{ display: 'inline-block', verticalAlign: 'middle' }}
                    className="acms-admin-margin-right-mini"
                  >
                    {ACMS.i18n('media.add_tags_to_checked_items').replace(
                      '$1',
                      checkedLength,
                    )}
                  </div>
                  <div
                    className="acms-admin-select2"
                    style={{
                      display: 'inline-block',
                      width: '200px',
                      verticalAlign: 'middle',
                      marginRight: '5px',
                    }}
                  >
                    <Creatable
                      multi
                      onChange={this.onAddedLabelsChange.bind(this)}
                      value={selectedLabels}
                      options={tags.map((tag) => ({
                        label: tag,
                        value: tag,
                      }))}
                    />
                  </div>
                  <button
                    type="button"
                    className="acms-admin-btn"
                    onClick={this.addLabelToItems.bind(this)}
                  >
                    追加
                  </button>
                </>
              )}
            </div>
          </div>

          <form
            ref={(list: HTMLFormElement) => {
              this.list = list
            }}
            className="acms-admin-media-list"
            style={{ display: style === 'table' ? 'block' : 'none' }}
          >
            <div className="acms-admin-table-scroll-xs acms-admin-table-scroll-sm acms-admin-table-scroll-md">
              <table className="adminTable acms-admin-table-admin acms-admin-table-hover acms-admin-media-table">
                <thead className="acms-admin-table-heading acms-admin-media-heading">
                  <tr>
                    <th className="acms-admin-media-row-check acms-admin-table-nowrap">
                      {mode === 'edit' && (
                        <label
                          className="acms-admin-form-checkbox acms-admin-margin-none"
                          onChange={this.toggleAllCheck.bind(this)}
                        >
                          <input type="checkbox" />
                          <i className="acms-admin-ico-checkbox" />
                        </label>
                      )}
                    </th>
                    {menu.image && (
                      <th className="acms-admin-media-row-image acms-admin-table-nowrap">
                        {ACMS.i18n('media.image2')}
                      </th>
                    )}
                    {menu.id && (
                      <th className="acms-admin-media-row-id acms-admin-table-nowrap">
                        ID
                      </th>
                    )}
                    {menu.filename && (
                      <th
                        onClick={this.setOrder.bind(this, 'media_title')}
                        className={`acms-admin-media-row-title ${this.getThClassName(
                          'media_title',
                        )}`}
                      >
                        {ACMS.i18n('media.file_name')}
                      </th>
                    )}
                    {menu.tag && (
                      <th className="acms-admin-media-row-tag">
                        {ACMS.i18n('media.tag')}
                      </th>
                    )}
                    {menu.last_modified && (
                      <th
                        onClick={this.setOrder.bind(
                          this,
                          'media_last_modified',
                        )}
                        className={`acms-admin-media-row-date acms-admin-table-nowrap ${this.getThClassName(
                          'media_last_modified',
                        )}`}
                      >
                        {ACMS.i18n('media.updated_date')}
                      </th>
                    )}
                    {menu.upload_datetime && (
                      <th
                        onClick={this.setOrder.bind(this, 'media_datetime')}
                        className={`acms-admin-media-row-date acms-admin-table-nowrap ${this.getThClassName(
                          'media_datetime',
                        )}`}
                      >
                        {ACMS.i18n('media.created_date')}
                      </th>
                    )}
                    {menu.filesize && (
                      <th
                        onClick={this.setOrder.bind(this, 'media_filesize')}
                        className={`acms-admin-media-row-size acms-admin-table-nowrap ${this.getThClassName(
                          'media_filesize',
                        )}`}
                      >
                        {ACMS.i18n('media.file_size')}
                      </th>
                    )}
                    {menu.blogname && (
                      <th className="acms-admin-media-row-blogname">
                        {ACMS.i18n('media.blog_name')}
                      </th>
                    )}
                    {mode === 'edit' && (
                      <th
                        className="acms-admin-media-row-action acms-admin-table-nowrap"
                        style={{ whiteSpace: 'nowrap' }}
                      >
                        {ACMS.i18n('media.action')}
                      </th>
                    )}
                  </tr>
                </thead>
                <tbody>
                  {items.map((item) => (
                    <tr
                      key={item.media_id}
                      onClick={(e) => {
                        this.rowClick(e, item)
                      }}
                    >
                      <td className="acms-admin-media-row-check acms-admin-table-nowrap">
                        {item.media_editable}
                        {(mode !== 'edit' || item.media_editable) && (
                          <label className="acms-admin-form-checkbox acms-admin-margin-none">
                            <input
                              type="checkbox"
                              name="checks[]"
                              value={`${item.media_bid}:${item.media_id}`}
                              checked={item.checked}
                              onChange={this.toggleCheck.bind(this, item)}
                            />
                            <i className="acms-admin-ico-checkbox" />
                          </label>
                        )}
                      </td>
                      {menu.image && (
                        <td className="acms-admin-media-row-image acms-admin-table-nowrap">
                          <ConditionalWrap
                            // eslint-disable-next-line react/no-unstable-nested-components
                            wrap={(chidren) => (
                              <a
                                href={item.media_permalink}
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                {chidren}
                              </a>
                            )}
                            condition={item.media_type === 'file'}
                          >
                            <div className="acms-admin-media-list-thumbnail-wrap">
                              <img
                                src={`${item.media_thumbnail}?date=${item.media_datetime}`}
                                className="acms-admin-media-list-thumbnail"
                                alt=""
                              />
                            </div>
                          </ConditionalWrap>
                        </td>
                      )}
                      {menu.id && (
                        <td className="acms-admin-media-row-id acms-admin-table-nowrap">
                          <span className="acms-admin-label">
                            {item.media_id}
                          </span>
                        </td>
                      )}
                      {menu.filename && (
                        <td className="acms-admin-media-row-title">
                          {item.media_title}
                        </td>
                      )}
                      {menu.tag && (
                        <td className="acms-admin-media-row-tag">
                          {item.media_label &&
                            item.media_label.split(delimiter).map((label) => (
                              <span
                                className="acms-admin-label acms-admin-label-info"
                                style={{
                                  display: 'inline-block',
                                  marginRight: '5px',
                                  marginTop: '5px',
                                }}
                                key={label}
                              >
                                {label}
                              </span>
                            ))}
                        </td>
                      )}
                      {menu.last_modified && (
                        <td className="acms-admin-media-row-date acms-admin-table-nowrap">
                          {dayjs(item.media_last_modified).format('YYYY/MM/DD')}
                          <br />
                          {dayjs(item.media_last_modified).format('HH:mm')}
                        </td>
                      )}
                      {menu.upload_datetime && (
                        <td className="acms-admin-media-row-date acms-admin-table-nowrap">
                          {dayjs(item.media_datetime).format('YYYY/MM/DD')}
                          <br />
                          {dayjs(item.media_datetime).format('HH:mm')}
                        </td>
                      )}
                      {menu.filesize && (
                        <td className="acms-admin-media-row-action acms-admin-table-nowrap">
                          {item.media_filesize && (
                            <>{formatBytes(item.media_filesize)}</>
                          )}
                          {item.media_size && item.media_type === 'image' && (
                            <span
                              style={{ display: 'block', marginTop: '5px' }}
                            >
                              {item.media_size}
                              px
                            </span>
                          )}
                        </td>
                      )}
                      {menu.blogname && (
                        <td className="acms-admin-media-row-blogname">
                          {item.media_blog_name}
                        </td>
                      )}
                      {mode === 'edit' && (
                        <td className="acms-admin-table-nowrap">
                          {item.media_editable && (
                            <button
                              type="button"
                              onClick={this.setItem.bind(this, item)}
                              className="acms-admin-btn"
                            >
                              {ACMS.i18n('media.edit')}
                            </button>
                          )}
                        </td>
                      )}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </form>

          <div
            className="acms-admin-media-grid"
            style={{ display: style === 'table' ? 'none' : 'flex' }}
          >
            {items.map((item, index) => (
              <div
                className="acms-admin-media-grid-item"
                key={item.media_id}
                style={{
                  width: `${150 * scale}px`,
                  height: `${150 * scale}px`,
                }}
              >
                <div
                  className={classnames('acms-admin-media-grid-figure', {
                    selected: item.checked,
                  })}
                  title={item.media_title}
                  onClick={(e) => {
                    if (e.shiftKey) {
                      this.toggleCheckFrom(oldItemId, item)
                    } else {
                      this.toggleCheck(item)
                      this.setState({
                        oldItemId: item.media_id,
                      })
                    }
                  }}
                >
                  <div className="acms-admin-media-grid-shadow" />
                  {item.media_type === 'file' && (
                    <div className="acms-admin-media-grid-img-wrap">
                      <img
                        className="acms-admin-media-grid-file-img"
                        src={item.media_thumbnail}
                        style={this.getFileStyle(stateItems[index])}
                        alt=""
                      />
                      <span className="acms-admin-media-grid-filename">
                        {getFileName(item.media_path)}
                      </span>
                    </div>
                  )}
                  {(item.media_type === 'image' ||
                    item.media_type === 'svg') && (
                    <img
                      src={`${item.media_thumbnail}?date=${item.media_last_modified}`}
                      style={this.getImageStyle(stateItems[index])}
                      id={`media-image-${index}`}
                      alt=""
                    />
                  )}
                  <div
                    className={classnames('acms-admin-media-grid-flame', {
                      selected: item.checked,
                    })}
                  >
                    <div className="acms-admin-media-grid-checkbox" />
                    {mode === 'edit' && item.media_editable && (
                      <div className="acms-admin-media-edit-list-btn-wrap">
                        <button
                          type="button"
                          className="acms-admin-media-edit-btn acms-admin-media-edit-list-btn"
                          onClick={(e) => {
                            e.stopPropagation()
                            this.setItem(item)
                          }}
                        >
                          {ACMS.i18n('media.edit')}
                        </button>
                      </div>
                    )}
                  </div>
                </div>
                <input
                  type="hidden"
                  name="checks[]"
                  value={`${item.media_bid}:${item.media_id}`}
                />
              </div>
            ))}
          </div>
          <div className="acms-admin-itemsAmount-container">
            <p>
              {(page - 1) * parseInt(limit, 10) + 1}
              {ACMS.i18n('media.items')} -
              {page * parseInt(limit, 10) > total
                ? total
                : page * parseInt(limit, 10)}
              {ACMS.i18n('media.items')} /{ACMS.i18n('media.all')}
              {total}
              {ACMS.i18n('media.items')}
            </p>
          </div>
          <div className="acms-admin-pager-container">
            {lastPage > 1 && (
              <ul className="acms-admin-pager">
                {page > 1 && (
                  <li>
                    <button
                      type="button"
                      onClick={() => {
                        this.fetchMediaList({
                          page: page - 1,
                        })
                      }}
                    >
                      «&nbsp;
                      {ACMS.i18n('media.prev')}
                    </button>
                  </li>
                )}
                {this.startPage() > 1 && (
                  <li>
                    <button
                      type="button"
                      onClick={() => {
                        this.fetchMediaList({
                          page: 1,
                        })
                      }}
                    >
                      1
                    </button>
                  </li>
                )}
                {this.startPage() > 1 && <li>...</li>}
                {this.pages().map((i) => (
                  <li
                    className={classnames({
                      cur: i === page,
                    })}
                    key={i.toString()}
                  >
                    <button
                      type="button"
                      onClick={() => {
                        this.fetchMediaList({
                          page: i,
                        })
                      }}
                    >
                      {i}
                    </button>
                  </li>
                ))}
                {lastPage > this.endPage() && <li>...</li>}
                {lastPage > this.endPage() && (
                  <li>
                    <button
                      type="button"
                      onClick={() => {
                        this.fetchMediaList({
                          page: lastPage,
                        })
                      }}
                    >
                      {lastPage}
                    </button>
                  </li>
                )}
                {lastPage > page && (
                  <li>
                    <button
                      type="button"
                      onClick={() => {
                        this.fetchMediaList({
                          page: page + 1,
                        })
                      }}
                    >
                      {ACMS.i18n('media.next')}
                      &nbsp;»
                    </button>
                  </li>
                )}
              </ul>
            )}
          </div>
          {deleting && <Splash message={ACMS.i18n('media.deleting_media')} />}
          <Notify
            message={ACMS.i18n('media.add_tags_to_media_confirm')}
            show={tagAdded}
            onFinish={() => {
              this.setState({
                tagAdded: false,
              })
            }}
          />
        </div>
      </div>
    )
  }
}
