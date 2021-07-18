import React, { CSSProperties } from 'react';
import classnames from 'classnames';
import MediaInsert from './media-insert';
import MediaUpdate from './media-update';
import DropZone from './drop-zone';
import { MediaItem } from '../types/media';

type MediaDropAreaProps = {
  mid: string
  thumbnail: string
  caption: string
  width: string
  height: string
  mediaType: 'image' | 'file' | 'all' | 'svg',
  mediaThumbnailType: 'image' | 'file' | 'svg',
  onChange: (mid: string) => any
  onError: () => any
}

type MediaDropAreaState = {
  mid: string,
  insertModalOpened: boolean,
  updateModalOpened: boolean,
  files: File[],
  thumbnail: string,
  landscape: boolean
}

export default class MediaDropArea extends React.Component<MediaDropAreaProps, MediaDropAreaState> {

  static defaultProps = {
    mediaType: 'image'
  }

  constructor(props) {
    super(props);
    this.state = {
      insertModalOpened: false,
      updateModalOpened: false,
      files: [],
      mid: props.mid,
      thumbnail: props.thumbnail,
      landscape: true
    }
  }

  componentDidMount() {
    const img = new Image();
    img.onload = () => {
      if (img.width < img.height) {
        this.setState({
          landscape: false
        })
      }
    }
    img.src = this.state.thumbnail;
  }

  onComplete = (files) => {
    this.setState({
      files: files.map(item => item.file),
      insertModalOpened: true
    })
  }

  uploadFile = (e) => {
    this.setState({
      files: Array.from(e.target.files),
      insertModalOpened: true
    });
  }

  onInsert = (items: MediaItem[]) => {
    const { mediaType } = this.props;
    const [item] = items;
    const fileOrImage = (item.media_type === 'image' || item.media_type === 'svg') ? 'image' : 'file';
    let landscape = true;
    if (item.media_size) {
      const [sizeX, sizeY] = item.media_size.split(' , ');
      if (sizeX < sizeY) {
        landscape = false;
      }
    }
    if (mediaType !== 'all' && mediaType !== fileOrImage) {
      this.props.onError();
      this.setState({
        insertModalOpened: false
      });
      return;
    }
    this.setState({
      insertModalOpened: false,
      mid: item.media_id,
      thumbnail: item.media_thumbnail,
      landscape
    });
    this.props.onChange(item.media_id);
  }

  onClose = () => {
    this.setState({
      insertModalOpened: false
    });
  }

  onUpdateModalClose = () => {
    this.setState({
      updateModalOpened: false
    })
  }

  onUpdateModalUpdate = (item: MediaItem) => {
    let landscape = true;
    if (item.media_size) {
      const [sizeX, sizeY] = item.media_size.split(' , ');
      if (sizeX < sizeY) {
        landscape = false;
      }
    }
    this.setState({
      updateModalOpened: false,
      mid: item.media_id,
      thumbnail: item.media_thumbnail,
      landscape
    });
    this.props.onChange(item.media_id);
  }

  remove = () => {
    this.setState({
      mid: ''
    });
    this.props.onChange('');
  }

  openEditModal = () => {
    this.setState({
      updateModalOpened: true
    });
  }

  render() {
    const { insertModalOpened, updateModalOpened, files, thumbnail, mid, landscape } = this.state;
    const { mediaType, caption, width, height, mediaThumbnailType } = this.props;

    const style: CSSProperties = {};

    if (width) {
      style.width = width;
    }
    if (height) {
      style.height = height;
    }

    const thumbnailType = mediaThumbnailType ? mediaThumbnailType : mediaType;

    return (<>
      <DropZone onComplete={this.onComplete}>
        <div>
          {!mid &&
            <div className="acms-admin-media-unit-droparea" style={style}>
              <p className="acms-admin-media-unit-droparea-text">{ACMS.i18n("media.add_new_media")}</p>
              <label className="acms-admin-media-unit-droparea-btn" style={{ cursor: 'pointer' }}>{ACMS.i18n("media.upload")}
                  {!insertModalOpened && <input type="file" onChange={this.uploadFile} style={{ display: 'none' }} multiple />}
              </label>
              <p className="acms-admin-media-unit-droparea-text">{ACMS.i18n("media.drop_file")}</p>
            </div>
          }
          {mid && <div className="acms-admin-media-unit-preview-wrap">
            <div className="acms-admin-media-unit-preview-overlay"></div>
            <button type="button" className="acms-admin-media-unit-preview-remove-btn" onClick={this.remove}></button>
            {(thumbnailType === 'image' || thumbnailType === 'svg') &&
              <img className="acms-admin-media-field-preview" src={`${thumbnail}`} alt="" />}
            {thumbnailType == 'file' &&
              <div className="acms-admin-media-unit-file-icon-wrap">
                <img className="acms-admin-media-unit-file-icon" src={`${thumbnail}`} alt="" />
                <p className="acms-admin-media-unit-file-caption">{caption}</p>
              </div>}
            <button type="button" className="acms-admin-media-edit-btn acms-admin-media-unit-preview-edit-btn" onClick={() => this.openEditModal(mid)}>{ACMS.i18n("media.edit")}</button>
          </div>}
        </div>
      </DropZone>
      {insertModalOpened && <MediaInsert
        onInsert={this.onInsert}
        {...(files.length ? { files } : {})}
        onClose={this.onClose}
        tab="upload"
        filetype={mediaType} />
      }
      {updateModalOpened && <MediaUpdate
        mid={`${mid}`}
        onClose={this.onUpdateModalClose}
        onUpdate={this.onUpdateModalUpdate}
      />}
    </>
    )
  }
}