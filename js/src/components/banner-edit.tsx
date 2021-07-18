import * as React from 'react';
import classNames from 'classnames';
import CodeMirror from 'react-codemirror';
import { setIn, removeIn } from 'immutable';
import { SortableContainer, SortableElement, SortableHandle, arrayMove } from 'react-sortable-hoc';
import dayjs from 'dayjs';
import DropZone from './drop-zone';
import TimePicker from './time-picker';
import DatePicker from './date-picker';
import { MediaItem } from '../types/media';
import { findAncestor } from '../lib/dom';
import { random, setTooltips } from '../lib/utility';
import MediaInsert from './media-insert';
import MediaUpdate from './media-update';
import 'react-toggle/style.css';
import 'codemirror/lib/codemirror.css';
import 'codemirror/mode/javascript/javascript';
import 'codemirror/mode/xml/xml';
import { getOffset } from '../utils';


const DragHandle = SortableHandle(() => <span className="acms-admin-icon-sort acms-admin-banner-edit-item-handle" />);

interface BannerItem {
  id: string,
  media_banner_type: 'image' | 'source',
  media_banner_attr1?: string,
  media_banner_attr2?: string,
  media_banner_source: '',
  media_banner_status: 'true' | 'false',
  media_banner_target: 'true' | 'false',
  media_banner_preview: string,
  media_banner_datestart: string,
  media_banner_timestart: string,
  media_banner_dateend: string,
  media_banner_timeend: string,
  media_banner_mid: string,
  media_banner_alt: string,
  media_banner_landscape?: boolean,
  media_banner_link: string,
  media_banner_override_link: string,
  toggle: boolean
}

interface BannerEditProps {
  attr1: string,
  attr2: string,
  tooltip1: string,
  tooltip2: string,
  hide1: string,
  hide2: string,
  items: BannerItem[],
  message: string
}

interface BannerEditState {
  isOpenAll: boolean,
  insertModalOpened: boolean,
  updateModalOpened: boolean,
  targetItem: BannerItem,
  modalType?: 'select' | 'upload',
  items: BannerItem[],
  files: File[],
  changed: boolean
  showEditor: boolean
}

export default class BannerEdit extends React.Component<BannerEditProps, BannerEditState> {

  page: HTMLElement;
  SortableList = this.renderList();
  SortableItem = this.renderItem();
  SortableUnitBox = this.renderUnitBox();

  constructor(props) {
    super(props);
    const items = props.items ? this.convertToBannerItems(props.items) : [];
    this.state = {
      isOpenAll: false,
      insertModalOpened: false,
      updateModalOpened: false,
      targetItem: null,
      modalType: 'upload',
      items: [{} as BannerItem, ...items],
      files: [],
      changed: false,
      showEditor: false
    };
  }

  componentDidMount() {
    const { items } = this.state;
    if (items.length < 2) {
      this.addItem('image');
    }
    const modal = findAncestor(this.page, '#js-module_management');
    if (!modal) {
      this.setState({
        showEditor: true
      });
      return;
    }
    // モーダルのなかでバナーを編集する場合、CodeMirrorがモーダルの表示より早く表示された場合正しくレンダーされないことがあるので
    setTimeout(() => {
      this.setState({
        showEditor: true
      });
    }, 500);
  }



  componentDidUpdate() {
    if (this.state.changed) {
      $('.js-config-not-saved').addClass('active');
    }
    setTooltips(this.page);
  }

  defineDefaultItem(): BannerItem {
    return {
      id: random(10),
      media_banner_source: "",
      media_banner_datestart: dayjs().format('YYYY-MM-DD'),
      media_banner_timestart: "00:00:00",
      media_banner_dateend: "9999-12-31",
      media_banner_timeend: "23:59:59",
      media_banner_landscape: true,
      media_banner_attr1: "",
      media_banner_attr2: "",
      media_banner_alt: "",
      media_banner_status: "true",
      media_banner_target: "false",
      media_banner_type: "image",
      media_banner_preview: "",
      media_banner_mid: "",
      media_banner_link: "",
      media_banner_override_link: "",
      toggle: true
    };
  }

  convertToBannerItems(items): BannerItem[] {
    return items.map((item) => ({
      ...item,
      id: random(10)
    }));
  }

  addItem(type: 'image' | 'source') {
    const { items } = this.state;
    const defaultItem = this.defineDefaultItem();
    const item = setIn(defaultItem, ['media_banner_type'], type);
    const findIndex = items.findIndex((item) => !item.id);
    const newItems = [...items.slice(0, findIndex + 1), item, ...items.slice(findIndex + 1)];

    this.setState({
      items: [...newItems],
      changed: true
    });
  }

  removeItem(id: string) {
    const { items } = this.state;
    const index = items.findIndex((item) => {
      if (item.id === id) {
        return true;
      }
      return false;
    });
    this.setState({
      items: removeIn(items, [index]),
      changed: true
    });
  }

  updateItem(id: string, key: keyof BannerItem, value) {
    const { items } = this.state;
    const item = items.find((item) => item.id === id);
    const newItem = setIn(item, [key], value);
    const index = items.findIndex((item) => item.id === id);
    this.setState({
      items: setIn(items, [index], newItem),
      changed: true
    });
  }

  openInsertModal(item) {
    this.setState({
      targetItem: item,
      modalType: 'select',
      insertModalOpened: true
    });
  }

  openUploadModal(item) {
    this.setState({
      targetItem: item,
      modalType: 'upload',
      insertModalOpened: true
    });
  }

  openUpdateModal(item) {
    this.setState({
      targetItem: item,
      updateModalOpened: true
    });
  }

  setNewItem(item?: MediaItem, banner: BannerItem = null, type: 'image' | 'source' = 'image'): BannerItem {
    const defaultItem = banner ? banner : this.defineDefaultItem();
    let media_banner_landscape = true;

    if (item.media_size) {
      const [sizeX, sizeY] = item.media_size.split(' , ');
      if (sizeX < sizeY) {
        media_banner_landscape = false;
      }
    }

    return Object.assign({}, defaultItem, {
      media_banner_mid: item.media_id,
      media_banner_preview: item.media_thumbnail,
      media_banner_type: type,
      media_banner_link: item.media_link,
      media_banner_landscape
    });
  }

  onInsert(newItems: MediaItem[]) {
    const { targetItem, items } = this.state;
    const index = items.findIndex((item) => item.id === targetItem.id);
    const newItem = this.setNewItem(newItems[0], items[index]);

    let setinItems = setIn(items, [index], newItem);

    if (newItems.length > 1) {
      const firstItems = [...setinItems.slice(0, index + 1)];
      const lastItems = [...setinItems.slice(index + 1)];
      const middleItems = [...newItems.slice(1).map((item) => {
        return this.setNewItem(item, null);
      })];
      setinItems = [...firstItems, ...middleItems, ...lastItems];
    }

    this.setState({
      items: setinItems,
      insertModalOpened: false,
      changed: true,
      files: []
    });
  }

  onUpdateModalUpdate(item: MediaItem) {
    const { targetItem, items } = this.state;
    const index = items.findIndex((item) => item.id === targetItem.id);
    const banner = items[index];
    const newItem = this.setNewItem(item, banner);
    const newItems = setIn(items, [index], newItem);
    this.setState({
      items: newItems,
      updateModalOpened: false,
      changed: true
    });
  }

  onClose() {
    this.setState({
      insertModalOpened: false,
      files: []
    });
  }

  openAll() {
    const items = this.state.items;
    items.forEach((item) => {
      item.toggle = true;
    });
    this.setState({
      isOpenAll: true,
      items
    });
  }

  closeAll() {
    const items = this.state.items;
    items.forEach((item) => {
      item.toggle = false;
    });
    this.setState({
      isOpenAll: false
    });
  }

  onUpdateModalClose() {
    this.setState({
      updateModalOpened: false
    });
  }

  onSortStart = () => {
    const modal = findAncestor(this.page, '#js-module_management');
    if (!modal) {
      return;
    }
    modal.style.webkitUserSelect = 'none';
  }

  onSortEnd = ({ oldIndex, newIndex }) => {
    const { items } = this.state;
    this.setState({
      items: arrayMove(items, oldIndex, newIndex),
      changed: true
    })
  }

  scrollToActiveElement = () => {
    setTimeout(() => {
      const { activeElement } = document;
      const offset = getOffset(activeElement);
      window.scrollTo(0, offset.top);
    }, 100);
  }

  renderList() {
    return SortableContainer(({ children }) => {
      return (<div className="acms-admin-banner-edit-container">{children}</div>);
    });
  }

  getValueByTime(time: Date) {
    dayjs(time).format('HH:ii:ss');
  }

  renderUnitBox() {
    return SortableElement(() => (
      <div className="acms-admin-banner-edit-item-wrap acms-admin-banner-edit-unit-box acms-admin-banner-sortable-editor" style={{ margin: '0 0 15px 0' }}>
        <div className="acms-admin-banner-edit-unit-box-inner">
          <DragHandle />
          <div className="acms-admin-banner-edit-btn-group">
            <span className="acms-admin-inline-btn" style={{ fontSize: '14px', verticalAlign: 'middle', margin: '5px 15px 0 0' }}>{ACMS.i18n("media.add")}</span>
            <div className="acms-admin-inline-btn">
              <button className="acms-admin-btn" onClick={this.addItem.bind(this, 'image')} type="button">{ACMS.i18n("media.media")}</button>
            </div>
            <div className="acms-admin-inline-btn">
              <button className="acms-admin-btn" style={{ marginRight: '5px' }} onClick={this.addItem.bind(this, 'source')} type="button">{ACMS.i18n("media.source")}</button>
            </div>
          </div>
        </div>
      </div>
    ));
  }

  onComplete = (files, item) => {
    this.setState({ files: files.map(item => item.file) });
    this.openUploadModal(item);
  }

  uploadFile(e, item) {
    this.setState({
      files: Array.from(e.target.files)
    });
    this.openUploadModal(item);
  }

  clearMediaItem(id) {
    const { items } = this.state;
    const index = items.findIndex((item) => {
      if (item.id === id) {
        return true;
      }
      return false;
    });
    this.setState({
      items: setIn(items, [index, 'media_banner_mid'], ''),
      changed: true
    });
  }

  renderOpenDate(item) {
    return (
      <tr>
        <th>{ACMS.i18n("media.open_period")}<i className="acms-admin-icon-tooltip js-acms-tooltip-hover acms-admin-margin-left-mini" data-acms-tooltip={ACMS.i18n("media.banner_open_date")}></i></th>
        <td>
          <DatePicker style={{ maxWidth: '95px' }} value={item.media_banner_datestart} onChange={(value) => {
            this.updateItem(item.id, 'media_banner_datestart', value);
          }} />
          &nbsp;
          <TimePicker style={{ maxWidth: '95px' }} value={item.media_banner_timestart} onChange={(value) => {
            this.updateItem(item.id, 'media_banner_timestart', value);
          }} />
          &nbsp;
          〜
          &nbsp;
          <DatePicker style={{ maxWidth: '95px' }} value={item.media_banner_dateend} onChange={(value) => {
            this.updateItem(item.id, 'media_banner_dateend', value);
          }} />
          &nbsp;
          <TimePicker style={{ maxWidth: '95px' }} value={item.media_banner_timeend} onChange={(value) => {
            this.updateItem(item.id, 'media_banner_timeend', value);
          }} />
        </td>
      </tr>)
  }

  renderItem() {
    const { attr1, attr2, hide1, hide2, tooltip1, tooltip2 } = this.props;
    return SortableElement(({ item, insertModalOpened, length, num, showEditor }: { item: BannerItem, insertModalOpened: boolean, length: number, num: number, showEditor: boolean }) => (
      <div
        className={classNames('acms-admin-form acms-admin-banner-edit-item-wrap acms-admin-banner-sortable-editor', { 'acms-admin-nested-selected': item.selected, 'acms-admin-nested-private': !item.media_banner_status })}
        key={item.id}
      >
        <DropZone
          onComplete={(files) => {
            this.onComplete(files, item);
          }}
        >
          <div className={classNames("acms-admin-banner-edit-item", {
            "acms-admin-banner-edit-item-hide": item.media_banner_status === 'false',
            "acms-admin-banner-edit-item-source": item.media_banner_type === 'source'
          })}>
            <div style={{ position: 'relative', zIndex: 1 }}>
              <div className="acms-admin-banner-edit-header">
                <DragHandle />
                <select style={{marginRight: '5px'}}
                  onChange={(e) => {
                      this.onSortEnd({
                        oldIndex: num,
                        newIndex: parseInt(e.target.value, 10)
                      })
                      this.scrollToActiveElement();
                    }
                  }>
                  {(() => {
                    const items = [];
                    for (let i = 0; i < length - 1; i++) {
                      items.push(<option selected={i === num - 1}>{i + 1}</option>)
                    }
                    return items;
                  })()}
                </select>
                <select defaultValue={item.media_banner_status === 'true' ? 'open' : 'close'}
                  onChange={(e) => {
                    if (e.target.value === 'open') {
                      this.updateItem(item.id, 'media_banner_status', 'true');
                    } else {
                      this.updateItem(item.id, 'media_banner_status', 'false');
                    }
                  }}
                >
                  <option value="open">{ACMS.i18n("media.open")}</option>
                  <option value="close">{ACMS.i18n("media.close")}</option>
                </select>
                <button type="button" className="acms-admin-banner-edit-remove-btn" onClick={this.removeItem.bind(this, item.id)}>
                  <i className="acms-admin-icon-cross"></i>
                </button>
                {item.media_banner_type === 'image' ? <span className="acms-admin-banner-edit-header-type-label">{ACMS.i18n("media.media")}</span> : <span className="acms-admin-banner-edit-header-type-label">{ACMS.i18n("media.source")}</span>}
              </div>
              <div className="acms-admin-banner-edit">
                <div className="acms-admin-nested-item-inner">
                  <div style={{ width: '100%' }}>
                    {item.media_banner_type === 'image' &&
                      <>
                        <div className="acms-admin-banner-edit-item-inner" style={{ marginTop: '0' }}>
                          <div className="acms-admin-banner-edit-item-thumb-wrap" style={{ position: 'relative' }}>
                            <div>

                              {!item.media_banner_mid && <div className="acms-admin-banner-edit-droparea">
                                <p className="acms-admin-banner-edit-droparea-text">{ACMS.i18n("media.add_new_media")}</p>
                                <label className="acms-admin-banner-edit-droparea-btn" style={{ cursor: 'pointer' }}>{ACMS.i18n("media.upload")}
                                {!insertModalOpened && <input type="file" onChange={(e) => {
                                    this.uploadFile(e, item)
                                  }} style={{ display: 'none' }} multiple />}
                                </label>
                                <p className="acms-admin-banner-edit-droparea-text">{ACMS.i18n("media.drop_file")}</p>
                              </div>}
                              {item.media_banner_mid &&
                                <div className="acms-admin-banner-edit-preview-wrap">
                                  <div className="acms-admin-banner-edit-preview-overlay"></div>
                                  <button type="button" className="acms-admin-banner-edit-preview-remove-btn" onClick={() => this.clearMediaItem(item.id)}></button>
                                  <div className="acms-admin-banner-edit-preview-edit-overlay"></div>
                                  <img className="acms-admin-banner-edit-preview" src={`${item.media_banner_preview}`} alt="" />
                                  <button type="button" className="acms-admin-media-edit-btn acms-admin-banner-edit-preview-edit-btn" onClick={() => {
                                    this.openUpdateModal(item);
                                  }}>{ACMS.i18n("media.edit")}</button>
                                </div>}
                            </div>
                          </div>
                          <div className="acms-admin-banner-edit-content">
                            <table className="acms-admin-banner-edit-table">
                              <tr>
                                <th className="acms-admin-table-nowrap">{ACMS.i18n("media.media_id")}</th>
                                <td>
                                  {item.media_banner_mid &&
                                    <span className="acms-admin-label acms-admin-label-default" style={{ marginRight: '5px' }}>{item.media_banner_mid}</span>}
                                  <button type="button" className="acms-admin-btn" onClick={() => {
                                    this.openInsertModal(item);
                                  }}>{ACMS.i18n("media.select_from_media")}</button>&nbsp;
                              </td>
                              </tr>
                              <tr>
                                <th className="acms-admin-table-nowrap">{ACMS.i18n("media.alt")}<i className="acms-admin-icon-tooltip js-acms-tooltip-hover acms-admin-margin-left-mini" data-acms-tooltip={ACMS.i18n("media.tooltip_alt")}></i></th>
                                <td>
                                  <input
                                    type="text"
                                    className="acms-admin-form-width-full"
                                    id={`media_banner-item-alt-${item.id}`}
                                    value={item.media_banner_alt}
                                    onInput={(e) => { this.updateItem(item.id, 'media_banner_alt', e.target.value); }}
                                  />
                                </td>
                              </tr>
                              <tr>
                                  <th className="acms-admin-table-nowrap">
                                    {ACMS.i18n("media.url_link_to")}
                                    <i className="acms-admin-icon-tooltip js-acms-tooltip-hover acms-admin-margin-left-mini" data-acms-tooltip={ACMS.i18n("media.tooltip_link")}></i>
                                  </th>
                                  <td>
                                    {/* {item.media_banner_mid && <button type="button" className="acms-admin-btn" onClick={() => {
                                      this.openUpdateModal(item);
                                    }}>{ACMS.i18n("media.media_edit")}</button>}&nbsp;
                                    <span style={{ marginRight: '5px', display: 'inline-block' }}>
                                      <a href={item.media_banner_link} target="_blank" rel="noopener noreferrer">{item.media_banner_link}</a>
                                    </span> */}
                                    <input type="text"
                                      value={item.media_banner_override_link}
                                      className="acms-admin-form-width-full"
                                      placeholder={item.media_banner_link ? `メディア: ${item.media_banner_link}` : ''}
                                      onChange={(e) => {
                                        this.updateItem(item.id, 'media_banner_override_link', e.target.value);
                                      }}
                                    />
                                    <div>
                                      <div className="acms-admin-form-checkbox">
                                        <input type="checkbox" checked={item.media_banner_target === 'true'}
                                          id={`media_banner-item-target-${item.id}`}
                                          value="true"
                                          onChange={(e) => {
                                            if (item.media_banner_target === 'true') {
                                              this.updateItem(item.id, 'media_banner_target', '');
                                            } else {
                                              this.updateItem(item.id, 'media_banner_target', 'true');
                                            }
                                          }}
                                        />
                                        <label htmlFor={`media_banner-item-target-${item.id}`}>
                                          <i className="acms-admin-ico-checkbox"></i>{ACMS.i18n("media.new_window")}
                                      </label>
                                      </div>
                                    </div>
                                  </td>
                              </tr>
                              {hide1 !== 'true' && <tr>
                                <th className="acms-admin-table-nowrap">{attr1 ? attr1 : ACMS.i18n("media.attr1")} <i className="acms-admin-icon-tooltip js-acms-tooltip-hover acms-admin-margin-left-mini" data-acms-tooltip={tooltip1 ? tooltip1 : ACMS.i18n("media.tooltip_attr1")}></i></th>
                                <td>
                                  <input type="text" className="acms-admin-form-width-full" id={`media_banner-item-attr1-${item.id}`} value={item.media_banner_attr1} onInput={(e) => { this.updateItem(item.id, 'media_banner_attr1', e.target.value); }} style={{ marginRight: '5px' }} />
                                </td>
                              </tr>}
                              {hide2 !== 'true' && <tr>
                                <th className="acms-admin-table-nowrap">{attr2 ? attr2 : ACMS.i18n("media.attr2")} <i className="acms-admin-icon-tooltip js-acms-tooltip-hover acms-admin-margin-left-mini" data-acms-tooltip={tooltip2 ? tooltip2 : ACMS.i18n("media.tooltip_attr2")}></i></th>
                                <td>
                                  <input type="text" className="acms-admin-form-width-full" id={`media_banner-item-attr2-${item.id}`} value={item.media_banner_attr2} onInput={(e) => { this.updateItem(item.id, 'media_banner_attr2', e.target.value); }} />
                                </td>
                              </tr>}
                              {this.renderOpenDate(item)}
                            </table>
                          </div>
                        </div>
                      </>
                    }
                    {(item.media_banner_type === 'source' && showEditor) &&
                      <>
                        <CodeMirror
                          value={item.media_banner_source}
                          options={{ mode: 'xml' }}
                          style={{ maxWidth: '820px', borderRadius: '3px' }}
                          onChange={(code) => {
                            this.updateItem(item.id, 'media_banner_source', code);
                          }}
                        />
                      </>
                    }
                    {item.media_banner_type === 'source' && <div className="acms-admin-banner-edit-item-inner">
                      <table className="acms-admin-banner-edit-table acms-admin-banner-edit-source-table">
                        {this.renderOpenDate(item)}
                      </table>
                    </div>}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </DropZone>
        <textarea style={{ display: 'none' }} name="media_banner_source[]" value={item.media_banner_source ? item.media_banner_source : ''}></textarea>
        <input type="hidden" name="media_banner_datestart[]" value={item.media_banner_datestart ? item.media_banner_datestart : ''} />
        <input type="hidden" name="media_banner_timestart[]" value={item.media_banner_timestart ? item.media_banner_timestart : ''} />
        <input type="hidden" name="media_banner_dateend[]" value={item.media_banner_dateend ? item.media_banner_dateend : ''} />
        <input type="hidden" name="media_banner_timeend[]" value={item.media_banner_timeend ? item.media_banner_timeend : ''} />
        <input type="hidden" name="media_banner_mid[]" value={item.media_banner_mid ? item.media_banner_mid : ''} />
        <input type="hidden" name="media_banner_attr1[]" value={item.media_banner_attr1 ? item.media_banner_attr1 : ''} />
        <input type="hidden" name="media_banner_attr2[]" value={item.media_banner_attr2 ? item.media_banner_attr2 : ''} />
        <input type="hidden" name="media_banner_status[]" value={item.media_banner_status ? item.media_banner_status : ''} />
        <input type="hidden" name="media_banner_target[]" value={item.media_banner_target ? item.media_banner_target : ''} />
        <input type="hidden" name="media_banner_type[]" value={item.media_banner_type} />
        <input type="hidden" name="media_banner_alt[]" value={item.media_banner_alt} />
        <input type="hidden" name="media_banner_link[]" value={item.media_banner_override_link} />
      </div>
    ))
  }

  render() {
    const { insertModalOpened, updateModalOpened, targetItem, modalType, items, files, showEditor } = this.state;
    const { SortableList, SortableItem, SortableUnitBox } = this;
    const { length } = items;
    return (
      <div ref={(page) => this.page = page}>
        <SortableList useDragHandle={true} axis="y" onSortStart={this.onSortStart} onSortEnd={this.onSortEnd} useWindowAsScrollContainer={true} helperClass="acms-admin-dragging">
          {items.map((item, index) => {
            if (item.id) {
              return (<SortableItem item={item} index={index} num={index} key={`item-${item.id}`} insertModalOpened={insertModalOpened} length={length} showEditor={showEditor} />)
            } else {
              return (<SortableUnitBox index={index} key={`item-${item.id}`} />)
            }
          })}
        </SortableList>
        <input type="hidden" name="@media_banner[]" value="media_banner_source" />
        <input type="hidden" name="config[]" value="media_banner_source" />
        <input type="hidden" name="@media_banner[]" value="media_banner_datestart" />
        <input type="hidden" name="config[]" value="media_banner_datestart" />
        <input type="hidden" name="@media_banner[]" value="media_banner_timestart" />
        <input type="hidden" name="config[]" value="media_banner_timestart" />
        <input type="hidden" name="@media_banner[]" value="media_banner_dateend" />
        <input type="hidden" name="config[]" value="media_banner_dateend" />
        <input type="hidden" name="@media_banner[]" value="media_banner_timeend" />
        <input type="hidden" name="config[]" value="media_banner_timeend" />
        <input type="hidden" name="@media_banner[]" value="media_banner_mid" />
        <input type="hidden" name="config[]" value="media_banner_mid" />
        <input type="hidden" name="@media_banner[]" value="media_banner_attr1" />
        <input type="hidden" name="config[]" value="media_banner_attr1" />
        <input type="hidden" name="@media_banner[]" value="media_banner_attr2" />
        <input type="hidden" name="config[]" value="media_banner_attr2" />
        <input type="hidden" name="@media_banner[]" value="media_banner_status" />
        <input type="hidden" name="config[]" value="media_banner_status" />
        <input type="hidden" name="@media_banner[]" value="media_banner_target" />
        <input type="hidden" name="config[]" value="media_banner_target" />
        <input type="hidden" name="@media_banner[]" value="media_banner_type" />
        <input type="hidden" name="config[]" value="media_banner_type" />
        <input type="hidden" name="config[]" value="@media_banner" />
        <input type="hidden" name="config[]" value="media_banner_alt" />
        <input type="hidden" name="@media_banner[]" value="media_banner_alt" />
        <input type="hidden" name="config[]" value="media_banner_link" />
        <input type="hidden" name="@media_banner[]" value="media_banner_link" />
        {insertModalOpened && <MediaInsert
          onInsert={this.onInsert.bind(this)}
          onClose={this.onClose.bind(this)}
          tab={modalType}
          {...(files.length ? { files } : {})}
          filetype="image" />
        }
        {updateModalOpened && <MediaUpdate
          mid={targetItem.media_banner_mid}
          onClose={this.onUpdateModalClose.bind(this)}
          onUpdate={this.onUpdateModalUpdate.bind(this)}
        />}
      </div>
    );
  }
}
