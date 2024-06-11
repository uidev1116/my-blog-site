import React, { Component } from 'react'
import Select from 'react-select'
import 'react-select/dist/react-select.css'
import debounce from 'debounce-promise'
import {
  SortableContainer,
  SortableElement,
  SortableHandle,
  arrayMove,
} from 'react-sortable-hoc'
import axiosLib from '../lib/axios'

const DragHandle = SortableHandle(() => (
  <td
    className="acms-admin-table-nowrap item-handle"
    style={{ cursor: 'pointer' }}
  >
    <i className="acms-admin-icon-sort" aria-hidden="true" />
  </td>
))

const SortableItem = SortableElement(({ item, removeItem, type }) => (
  <tr className="acms-admin-sortable-item sortable-item" key={item.id}>
    <DragHandle />
    <td>
      <input type="hidden" name="related[]" value={item.id} />
      <input type="hidden" name="related_type[]" value={type} />
      <div
        style={{
          display: 'inline-block',
          verticalAlign: 'middle',
          width: 50,
          marginRight: 20,
        }}
      >
        {item.image && item.image.length > 5 && (
          <img src={item.image} alt={item.title} width={50} />
        )}
      </div>
      <div style={{ display: 'inline-block', verticalAlign: 'middle' }}>
        {item.title}
        {item.categoryName && (
          <span
            className="acms-admin-icon-category acms-admin-icon-mute acms-admin-margin-right-mini"
            style={{ marginLeft: '5px', display: 'inline-block' }}
          >
            {item.categoryName}
          </span>
        )}
      </div>
      <div className="entryFormRelatedActions">
        <input
          type="button"
          className="acms-admin-btn-admin acms-admin-btn-admin-danger"
          value={ACMS.i18n('related_entry.remove')}
          style={{ marginRight: '5px' }}
          onClick={() => {
            removeItem(item.id)
          }}
        />
        <a
          href={item.url}
          className="acms-admin-btn-admin"
          target="_blank"
          rel="noopener noreferrer"
        >
          {ACMS.i18n('related_entry.check')}
        </a>
      </div>
    </td>
  </tr>
))

const SortableList = SortableContainer(
  ({ items, removeItem, type, moduleId }) => (
    <tbody>
      {items.map((item, index) => (
        <SortableItem
          key={`item-${item.id}`}
          index={index}
          item={item}
          removeItem={removeItem}
          type={type}
          moduleId={moduleId}
        />
      ))}
    </tbody>
  ),
)

export default class RelatedEntry extends Component {
  constructor(props) {
    super(props)
    this.state = {
      keyword: '',
      options: [],
      list: props.items,
    }
    this.getItemRequest = debounce(
      (keyword, moduleId, ctx) =>
        axiosLib({
          method: 'GET',
          url: ACMS.Library.acmsLink(
            {
              tpl: 'ajax/edit/autocomplete.json',
              bid: ACMS.Config.bid,
            },
            false,
          ),
          cache: true,
          responseType: 'json',
          params: {
            keyword,
            moduleId,
            ctx,
          },
        }).then((response) => {
          const { data } = response
          const maps = data.map((item) => ({
            ...item,
            value: item.value + item.id,
          }))
          const options = this.filterOption(maps)
          this.setState({
            options,
          })
          return { options }
        }),
      800,
    )
  }

  UNSAFE_componentWillReceiveProps(props) {
    if (props.items) {
      this.setState({
        list: props.items,
      })
    }
  }

  getItems(keyword) {
    if (!keyword) {
      return Promise.resolve({
        options: [],
      })
    }
    const { moduleId, ctx } = this.props

    return this.getItemRequest(keyword, moduleId, ctx)
  }

  onChange(target) {
    const { list } = this.state
    const { maxItem } = this.props
    const find = list.find((item) => {
      if (target.id === item.id) {
        return true
      }
      return false
    })
    if (find) {
      alert(ACMS.i18n('related_entry.already_registered')) // eslint-disable-line no-alert, no-console
      return
    }
    if (maxItem > 0 && list.length >= maxItem) {
      alert(ACMS.i18n('related_entry.max_items')) // eslint-disable-line no-alert, no-console
      return
    }
    this.setState({
      list: [...list, target],
    })
  }

  onSortEnd({ oldIndex, newIndex }) {
    this.setState({
      // eslint-disable-next-line react/no-access-state-in-setstate
      list: arrayMove(this.state.list, oldIndex, newIndex),
    })
  }

  removeItem(id) {
    if (!confirm(ACMS.i18n('related_entry.confirm_remove'))) {
      // eslint-disable-line no-alert, no-console
      return
    }
    const { list } = this.state
    const filtered = list.filter((item) => {
      if (item.id !== id) {
        return true
      }
      return false
    })
    this.setState({
      list: filtered,
    })
  }

  filterOption(options) {
    const { list } = this.state
    const filtered = options.filter((option) => {
      const find = list.find((item) => {
        if (item.id === option.id) {
          return true
        }
        return false
      })
      if (find) {
        return false
      }
      return true
    })
    return filtered
  }

  render() {
    const { list, options } = this.state
    const { type, title } = this.props
    const filteredOptions = this.filterOption(options)
    return (
      <table className="acms-admin-related-table">
        <tbody>
          <tr className="acms-admin-related-table-item">
            <th style={{ verticalAlign: 'top' }}>{title}</th>
            <td>
              <div className="acms-admin-related-list-wrap acms-admin-select2">
                <Select.Async
                  autosize={false}
                  options={filteredOptions}
                  loadOptions={this.getItems.bind(this)}
                  filterOptions={this.filterOption.bind(this, options)}
                  closeOnSelect={false}
                  onChange={this.onChange.bind(this)}
                  onSelectResetsInput={false}
                  placeholder={ACMS.i18n('related_entry.placeholder')}
                  searchPromptText={ACMS.i18n('related_entry.type_to_search')}
                />
              </div>
              {list.length !== 0 && (
                <table
                  className="adminTable acms-admin-table-sort"
                  style={{ marginTop: '10px', marginBottom: '0' }}
                >
                  <SortableList
                    items={list}
                    onSortEnd={this.onSortEnd.bind(this)}
                    removeItem={this.removeItem.bind(this)}
                    type={type}
                    useDragHandle
                  />
                </table>
              )}
              <input
                type="hidden"
                name="loaded_realted_entries[]"
                value={type || 'default'}
              />
            </td>
          </tr>
        </tbody>
      </table>
    )
  }
}
