import React from 'react';

import MediaInsert from './media-insert';
import MediaUpdate from './media-update';
import DropZone from './drop-zone';
import { MediaItem } from '../types/media';

type MediaUnitProps = {
  items?: MediaItem[];
  mediaSizes?: { value: string; label: string; selected: string }[];
  primaryImageId: string;
  id: string;
  bid: string;
  diff: string;
  active: string;
  mediaDir: string;
  rootDir: string;
  lang?: string;
  path: string;
  usePdfIcon: 'yes' | 'no';
  primary: 'true' | 'false';
  hasLink: 'true' | 'false';
  multiUpload: 'true' | 'false';
  enlarged: 'true' | 'false';
  overrideLink: string;
  overrideAlt: string;
  overrideCaption: string;
};

type MediaUnitState = {
  insertModalOpened: boolean;
  updateModalOpened: boolean;
  newMedia: boolean;
  noLink: 'yes' | 'no';
  noLarge: 'yes' | 'no';
  useIcon: 'yes' | 'no';
  modalType: 'upload' | 'select';
  link: string;
  targetId: string;
  items: MediaItem[];
  files: File[];
  landscape: boolean;
  alt: string;
  caption: string;
};

export default class MediaUnit extends React.Component<MediaUnitProps, MediaUnitState> {
  static defaultProps = {
    items: [],
    mediaSizes: [],
    lang: 'ja',
  };

  constructor(props: MediaUnitProps) {
    super(props);

    let newMedia = false;
    if (!props.items[0].media_id) {
      newMedia = true;
    }
    this.state = {
      insertModalOpened: false,
      updateModalOpened: false,
      modalType: 'upload',
      targetId: null,
      items: props.items,
      files: [],
      landscape: true,
      newMedia,
      noLarge: props.enlarged === 'true' ? 'no' : 'yes',
      noLink: props.hasLink === 'true' ? 'no' : 'yes',
      useIcon: props.usePdfIcon === 'yes' ? 'yes' : 'no',
      link: props.overrideLink,
      alt: props.overrideAlt,
      caption: props.overrideCaption,
    };
  }

  componentDidMount() {
    const [item] = this.state.items;
    const src = item.media_thumbnail;
    const img = new Image();
    img.onload = () => {
      if (img.width < img.height) {
        this.setState({
          landscape: false,
        });
      }
    };
    img.src = src;
  }

  openInsertModal = () => {
    this.setState({
      modalType: 'select',
      insertModalOpened: true,
    });
  };

  onInsert = (items) => {
    this.setState({
      items,
      insertModalOpened: false,
    });
  };

  onClose = () => {
    this.setState({
      insertModalOpened: false,
    });
  };

  onUpdateModalClose = () => {
    this.setState({
      updateModalOpened: false,
    });
  };

  onUpdateModalUpdate = (item) => {
    const { items, targetId } = this.state;
    const findIndex = items.findIndex((currentItem) => {
      if (currentItem.media_id === targetId) {
        return true;
      }
      return false;
    });
    const updated = [...items.slice(0, findIndex), item, ...items.slice(findIndex + 1)];
    this.setState({
      updateModalOpened: false,
      items: updated,
    });
  };

  removeMediaAt = (index: number) => {
    const { items } = this.state;
    const filtered = items.filter((item, i) => i !== index);
    this.setState({
      items: filtered,
    });
  };

  openEditModal = (targetId: string) => {
    this.setState({
      targetId,
      updateModalOpened: true,
    });
  };

  onComplete = (files) => {
    this.setState({
      files: files.map((item) => item.file),
      insertModalOpened: true,
      modalType: 'upload',
    });
  };

  uploadFile = (e) => {
    this.setState({
      files: Array.from(e.target.files),
      insertModalOpened: true,
      modalType: 'upload',
    });
  };

  render() {
    const {
      insertModalOpened,
      updateModalOpened,
      modalType,
      targetId,
      items,
      files,
      newMedia,
      noLarge,
      useIcon,
      link,
      alt,
      caption,
    } = this.state;
    const {
      mediaSizes,
      primaryImageId,
      id,
      active,
      rootDir,
      enlarged,
      usePdfIcon,
      primary,
      bid,
      lang,
      multiUpload,
    } = this.props;
    const [item] = items;

    return (
      <DropZone
        onComplete={(files) => {
          this.onComplete(files);
        }}
      >
        <table className="entryFormColumnSettingTable entryFormColumnTable">
          <tr className="entryFormFileControl">
            <td className="acms-admin-media-unit-preview-area">
              {(items.length === 0 || (items.length < 2 && !item.media_id)) && (
                <div>
                  <div className="acms-admin-media-unit-droparea">
                    <p className="acms-admin-media-unit-droparea-text">{ACMS.i18n('media.add_new_media')}</p>
                    {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
                    <label className="acms-admin-media-unit-droparea-btn" style={{ cursor: 'pointer' }}>
                      {ACMS.i18n('media.upload')}
                      {!insertModalOpened && (
                        <input type="file" onChange={this.uploadFile} style={{ display: 'none' }} multiple />
                      )}
                    </label>
                    <p className="acms-admin-media-unit-droparea-text">{ACMS.i18n('media.drop_file')}</p>
                  </div>
                  <input type="hidden" name={`media_id_${id}[]`} value="" />
                </div>
              )}
              {items.length > 0 && items.length < 2 && item.media_id && (
                <div className="acms-admin-media-unit-preview-wrap">
                  <div className="acms-admin-media-unit-preview-overlay" />
                  {/* eslint-disable-next-line jsx-a11y/control-has-associated-label */}
                  <button
                    type="button"
                    className="acms-admin-media-unit-preview-remove-btn"
                    onClick={() => this.removeMediaAt(0)}
                  />
                  <div className="acms-admin-media-unit-preview-edit-overlay" />
                  {(item.media_type === 'image' || item.media_type === 'svg') && (
                    <img className="acms-admin-media-unit-preview" src={`${item.media_thumbnail}`} alt="" />
                  )}
                  {item.media_type === 'file' && (
                    <div className="acms-admin-media-unit-file-icon-wrap">
                      <img className="acms-admin-media-unit-file-icon" src={`${item.media_thumbnail}`} alt="" />
                      <p className="acms-admin-media-unit-file-caption">{item.media_title}</p>
                    </div>
                  )}
                  <button
                    type="button"
                    className="acms-admin-media-edit-btn acms-admin-media-unit-preview-edit-btn"
                    onClick={() => this.openEditModal(item.media_id)}
                  >
                    編集
                  </button>
                  <input type="hidden" name={`media_id_${id}[]`} value={item && item.media_id} />
                </div>
              )}
            </td>

            <td className="entryFormFileControl">
              <table className="acms-admin-margin-bottom-mini" style={{ width: '100%' }}>
                <tr>
                  <th>ID</th>
                  <td>
                    <div style={{ display: 'inline-block' }}>
                      {item && item.media_id && (
                        <span className="acms-admin-label acms-admin-label-default acms-admin-margin-right-mini">
                          {item.media_id}
                        </span>
                      )}
                    </div>
                    <button type="button" className="acms-admin-btn-admin" onClick={this.openInsertModal}>
                      {ACMS.i18n('media.select_from_media')}
                    </button>
                    {active !== 'on' && (
                      <span style={{ color: 'red' }}>
                        {ACMS.i18n('media.media_unit_use1')}
                        <a href={`${rootDir}bid/${bid}/admin/config_function/`}>{ACMS.i18n('media.media_unit_use2')}</a>
                        {ACMS.i18n('media.media_unit_use3')}
                      </span>
                    )}
                  </td>
                </tr>
                {mediaSizes.length !== 0 && (
                  <tr>
                    <th>
                      <label htmlFor={`unit-media-size-${id}`}>{ACMS.i18n('media.size')}</label>
                    </th>
                    <td>
                      <select name={`media_size_${id}[]`} id={`unit-media-size-${id}`}>
                        {mediaSizes.map((mediaSize) => (
                          <option value={mediaSize.value} selected={!!mediaSize.selected} key={mediaSize.label}>
                            {mediaSize.label}
                          </option>
                        ))}
                      </select>
                      {item
                        && item.media_type === 'file'
                        && (item.media_pdf === 'yes' || (item.media_ext && item.media_ext.toUpperCase() === 'PDF')) && (
                          <div className="acms-admin-form-checkbox" style={{ marginLeft: '10px' }}>
                            <input type="hidden" name={`media_use_icon_${id}[]`} value={useIcon} />
                            <input
                              type="checkbox"
                              value="yes"
                              id={`input-checkbox-media_use_icon_${id}_${lang}`}
                              defaultChecked={usePdfIcon === 'yes'}
                              onChange={(e) => {
                                if (e.target.checked) {
                                  this.setState({
                                    useIcon: 'yes',
                                  });
                                } else {
                                  this.setState({
                                    useIcon: 'no',
                                  });
                                }
                              }}
                            />
                            <label htmlFor={`input-checkbox-media_use_icon_${id}_${lang}`}>
                              <i className="acms-admin-ico-checkbox" />
                              {ACMS.i18n('media.use_icon')}
                            </label>
                          </div>
                      )}
                    </td>
                  </tr>
                )}
                {items.length < 2 && (
                  <tr>
                    <th>{ACMS.i18n('media.caption')}</th>
                    <td>
                      <input
                        type="text"
                        name={`media_caption_${id}[]`}
                        id={`unit-media-caption-${id}`}
                        className="acms-admin-form-width-full"
                        defaultValue={caption}
                        placeholder={item && item.media_caption ? `${item.media_caption}` : ''}
                      />
                    </td>
                  </tr>
                )}
                {items.length < 2 && (
                  <tr>
                    <th>{ACMS.i18n('media.alt')}</th>
                    <td>
                      <input
                        type="text"
                        name={`media_alt_${id}[]`}
                        id={`unit-media-alt-text-${id}`}
                        className="acms-admin-form-width-full"
                        defaultValue={alt}
                        placeholder={item && item.media_alt ? `${item.media_alt}` : ''}
                      />
                    </td>
                  </tr>
                )}
                {items.length < 2
                  && item
                  && item.media_id
                  && (item.media_type === 'image' || item.media_type === 'svg') && (
                    <tr>
                      <th>{ACMS.i18n('media.url_link_to')}</th>
                      <td>
                        {item.media_link && (
                          <input
                            type="text"
                            name={`media_link_${id}[]`}
                            className="acms-admin-form-width-full"
                            placeholder={`${item.media_link}`}
                            defaultValue={link}
                          />
                        )}
                        {!item.media_link && (
                          <input
                            type="text"
                            name={`media_link_${id}[]`}
                            className="acms-admin-form-width-full"
                            defaultValue={link}
                          />
                        )}
                        {/* <button type="button" className="acms-admin-btn-admin" onClick={() => this.openEditModal(item.media_id)}>{ACMS.i18n("media.edit")}</button>&nbsp;
                  {(item && item.media_link) && <span className="js-media-link"><a href={item && item.media_link} target="_blank" rel="noopener noreferrer">{item && item.media_link}</a></span>} */}
                      </td>
                    </tr>
                )}

                {items.length < 2 && item && (item.media_type === 'image' || item.media_type === 'svg') && (
                  <tr>
                    <th>{ACMS.i18n('media.image_link')}</th>
                    <td>
                      <div className="acms-admin-form-checkbox">
                        <input type="hidden" name={`media_enlarged_${id}[]`} value={noLarge} />
                        <input
                          type="checkbox"
                          value="no"
                          id={`input-checkbox-media_enlarged_${id}_${lang}`}
                          defaultChecked={enlarged === 'true'}
                          onChange={(e) => {
                            if (e.target.checked) {
                              this.setState({
                                noLarge: 'no',
                              });
                            } else {
                              this.setState({
                                noLarge: 'yes',
                              });
                            }
                          }}
                        />
                        <label htmlFor={`input-checkbox-media_enlarged_${id}_${lang}`}>
                          <i className="acms-admin-ico-checkbox" />
                          {ACMS.i18n('media.no_image_link')}
                        </label>
                      </div>
                    </td>
                  </tr>
                )}
                {items.length < 2 && primaryImageId !== 'no' && item && item.media_type === 'image' && !newMedia && (
                  <tr>
                    <th>{ACMS.i18n('media.main_image')}</th>
                    <td>
                      <div className="acms-admin-form-radio">
                        <input
                          type="radio"
                          name="primary_image"
                          value={primaryImageId}
                          id={`input-radio-image_primary_image_${id}_${lang}`}
                          defaultChecked={primary === 'true'}
                        />
                        <label htmlFor={`input-radio-image_primary_image_${id}_${lang}`}>
                          <i className="acms-admin-ico-radio" />
                          {ACMS.i18n('media.set_as_main_image')}
                        </label>
                      </div>
                    </td>
                  </tr>
                )}
              </table>
              {!(items.length < 2 && item && (item.media_type === 'image' || item.media_type === 'svg')) && (
                <input type="hidden" name={`media_enlarged_${id}[]`} value="no" />
              )}
            </td>
          </tr>
          {items.length >= 2 && (
            <tr>
              <td colSpan={2}>
                <div className="acms-admin-media-unit-preview-group">
                  {items.map((item, index) => (
                    <div
                      className="acms-admin-media-unit-preview-wrap acms-admin-media-unit-preview-group-item"
                      key={item.media_id}
                    >
                      <div className="acms-admin-media-unit-preview-group-item-inner">
                        <div className="acms-admin-media-unit-preview-edit-overlay" />
                        {(item.media_type === 'image' || item.media_type === 'svg') && (
                        <img
                          className="acms-admin-media-unit-preview-group-img"
                          src={`${item.media_thumbnail}`}
                          alt=""
                        />
                        )}
                        {item.media_type === 'file' && (
                        <div className="acms-admin-media-unit-file-icon-wrap">
                          <img className="acms-admin-media-unit-file-icon" src={`${item.media_thumbnail}`} alt="" />
                          <p className="acms-admin-media-unit-file-caption">{item.media_title}</p>
                        </div>
                        )}
                        <input type="hidden" name={`media_id_${id}[]`} value={item && item.media_id} />
                      </div>
                      <div className="acms-admin-media-unit-preview-overlay" />
                      {/* eslint-disable-next-line jsx-a11y/control-has-associated-label */}
                      <button
                        type="button"
                        className="acms-admin-media-unit-preview-remove-btn"
                        onClick={() => this.removeMediaAt(index)}
                      />
                      <button
                        type="button"
                        className="acms-admin-media-edit-btn acms-admin-media-unit-preview-edit-btn"
                        onClick={() => this.openEditModal(item.media_id)}
                      >
                        {ACMS.i18n('media.edit')}
                      </button>
                    </div>
                  ))}
                </div>
              </td>
            </tr>
          )}
        </table>
        {insertModalOpened && (
          <MediaInsert
            onInsert={this.onInsert}
            radioMode={multiUpload === 'false'}
            {...(files.length ? { files } : {})}
            onClose={this.onClose}
            tab={modalType}
            filetype="all"
          />
        )}
        {updateModalOpened && (
          <MediaUpdate mid={targetId} onClose={this.onUpdateModalClose} onUpdate={this.onUpdateModalUpdate} />
        )}
      </DropZone>
    );
  }
}
