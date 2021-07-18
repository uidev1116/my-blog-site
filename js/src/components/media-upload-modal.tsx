import React, { Component, Fragment } from 'react';
import axios from 'axios';
import { Creatable } from './react-select-styled';
import classNames from 'classnames';
import { ExtendedFile, MediaItem } from '../types/media';
import DropZone from './drop-zone';
import ProgressBar from './progress-bar';
import ResizeImage from '../lib/resize-image/util';
import {formatBytes, random, getExt, setTooltips, dataURItoBlob} from '../lib/utility';
import readFiles from '../lib/read-files';
import * as actions from '../actions/media';

axios.defaults.headers['X-Requested-With'] = 'XMLHttpRequest';
const delimiter = ',';

type MediaUploadItem = {
  file: File,
  filetype: string,
  preview: string,
  id: string,
  idx: number,
  progress?: number,
  progressError: boolean,
  name: string,
  size: number,
  uploadedItem?: MediaItem
}

type MediaUploadModalProp = {
  showUploadButton: boolean,
  largeSize: number,
  actions: typeof actions,
  files?: File[],
  tags: string[]
}

type MediaUploadModalState = {
  items: MediaUploadItem[],
  label: string,
  loading: boolean,
  imgsOnStage: boolean
}

export default class MediaUploadModal extends Component<MediaUploadModalProp, MediaUploadModalState> {
  static defaultProps = {
    showUploadButton: true,
    tags: [],
    files: []
  }

  constructor(props) {
    super(props);
    this.state = {
      items: [],
      label: props.label,
      loading: false,
      imgsOnStage: false,
      hasUploadedItems: false // has already uploaded images
    };
  }

  componentDidMount() {
    readFiles(this.props.files).then((extededFiles) => {
      this.onComplete(extededFiles);
    })
  }

  createPdfThumbnail(file) {
    return new Promise((resolve, reject) => {
      if (!file) {
        resolve();
      }
      const reader = new FileReader();
      reader.onload = (event) => {
        import(/* webpackChunkName: "pdf2image" */'../lib/pdf2image').then(async ({ default: Pdf2Image }) => {
          const pdf2Image = new Pdf2Image(new Uint8Array(event.target.result));
          pdf2Image.getPageImage(1).then((image) => {
            resolve(image);
          }).catch((reason) => {
            reject(new Error(reason));
          });
        });
      };
      reader.readAsArrayBuffer(file);
    });
  }

  upload(blob: Blob | File, label: string, name: string, item: MediaUploadItem) {
    const { largeSize } = this.props;
    return new Promise(async (resolve) => {
      const fd = new FormData();
      fd.append('ACMS_POST_Media_Upload', 'true');
      if (label) {
        fd.append('tags', label);
      }
      const ext = getExt(name);
      if (ext.toLowerCase() === 'pdf') {
        const image = await this.createPdfThumbnail(blob);
        if (image) {
          fd.append('media_pdf_thumbnail', dataURItoBlob(image));
          fd.append('pdf_page',1);
        }
      }
      const randomName = random(10);
      fd.append('name', `${randomName}.${ext}`);
      fd.append('size', `${largeSize}`);
      fd.append('file', blob, name);
      fd.append('formToken', window.csrfToken);
      this.setProgressBar(item, 0);
      axios.post(location.href, fd, {
        onUploadProgress: (e) => {
          this.setProgressBar(item, 70 * (e.loaded / e.total));
        }
      }).then((res) => {
        if (res && res.data) {
          if (res.data.status === 'failure') {
            this.setProgressBar(item, 100, true);
          } else {
            this.setProgressBar(item, 100);
            setTimeout(() => {
              resolve(res.data);
            }, 800);
          }
        } else {
          resolve();
        }
      }).catch((err) => {
        console.log(err);
      });
    });
  }

  setProgressBar(item, progress, progressError = false) {
    const { items } = this.state;
    const findIndex = items.findIndex((file) => {
      if (file.id === item.id) {
        return true;
      }
      return false;
    });
    const renewedItem = Object.assign({}, item, {
      progress: progress,
      progressError
    });
    this.setState({
      items: [...items.slice(0, findIndex), renewedItem, ...items.slice(findIndex + 1)]
    });
  }

  removeFile(file) {
    const items = this.state.items;
    const index = items.findIndex((item) => {
      if (item.idx === file.idx) {
        return true;
      }
      return false;
    });
    this.setState({
      items: [...items.slice(0, index), ...items.slice(index + 1)]
    });
  }

  setUploadedItem(file, uploadedItem) {
    const items = this.state.items;
    const index = items.findIndex((item) => {
      if (item.idx === file.idx) {
        return true;
      }
      return false;
    });
    const selectedItem = items[index];
    const updatedItem = Object.assign({}, selectedItem, {
      uploadedItem,
      progress: -1
    });

    const copyItems = this.state.items.slice();
    copyItems[index] = updatedItem;

    this.setState({
      items: copyItems
    });
  }

  editItem(item) {
    this.props.actions.setItem(item.uploadedItem);
  }

  uploadItems() {
    return new Promise((resolve) => {
      const { items, label } = this.state;
      const { actions } = this.props;
      const promiseArr = [];
      const resizeImage = new ResizeImage();
      this.setState({
        loading: true,
        hasUploadedItems: true
      });
      const [resizeType ,largeSize] = ACMS.Config.lgImg.split(':');

      [].forEach.call(items, (item: MediaUploadItem) => {
        const { name, filetype, file } = item;
        const promise = new Promise((resolve) => {
          if (filetype === 'image' && file.type.indexOf('svg') === -1 && file.type.indexOf('gif') === -1) {
            resizeImage.getBlobFromFile(file, resizeType, parseInt(largeSize, 10)).then(({ blob }) => {
              this.upload(blob, label, name, item).then((uploadedItem) => {
                this.setUploadedItem(item, uploadedItem);
                resolve(uploadedItem);
              });
            });
          } else {
            this.upload(file, label, name, item).then((uploadedItem) => {
              this.setUploadedItem(item, uploadedItem);
              resolve(uploadedItem);
            });
          }
        });
        promiseArr.push(promise);
      });
      Promise.all(promiseArr).then((results) => {
        this.setState({
          loading: false
        });
        actions.fetchMediaList(null);
        resolve(results);
      });
    });
  }

  setLabel(e) {
    this.setState({
      label: e.target.value
    });
  }

  hideModal() {
    this.props.actions.setUpload(false);
  }

  onComplete(files: ExtendedFile[]) {
    let items = [...this.state.items, ...files.map(file => ({
      file: file.file,
      filetype: file.filetype,
      preview: file.preview,
      name: file.file.name,
      size: file.file.size,
      idx: -1,
      id: ''
    }))].filter((item:MediaUploadItem)  => {
      if (item.uploadedItem) {
        return false;
      }
      return true;
    });
    items.forEach((item, index) => {
      item.idx = index;
      item.id = random(10);
    });
    if (items.length > 20) {
      alert(ACMS.i18n("media.more_than_20_not_allowed"));
      items = items.slice(0, 20);
    }
    if (items.length) {
      this.setState({ items, hasUploadedItems: false, imgsOnStage: true });
    }
  }

  makeTags(label) {
    if (!label) {
      return null;
    }
    const labels = label.split(delimiter);
    return labels.map(label => ({
      value: label,
      label
    }));
  }

  addTags(tags) {
    const label = tags.reduce((val, tag, idx) => {
      if (idx === 0) {
        return tag.value;
      }
      return `${val}${delimiter}${tag.value}`;
    }, '');

    this.setState({
      label
    });
  }

  // @todo
  // getFileIcon(filename) {
  //   const ext = getExt(filename);
  //   if (ext === 'zip' || ext === 'lzh') {
  //     return 'archive.png';
  //   } else if (ext === 'mp4' || ext === 'wmv' || ext === 'flv' || ext === 'mov') {
  //     return 'audio.png';
  //   } else if (ext === 'doc') {
  //     return 'doc.png'
  //   } else if (ext === 'docx') {
  //     return 'docx.png';
  //   } else if (ext === 'ppt') {
  //     return 'ppt.png';
  //   } else if (ext === 'pptx') {
  //     return 'pptx.png';
  //   } else if (ext === 'xls') {
  //     return 'xls.png';
  //   } else if (ext === 'xlsx') {
  //     return 'xlsx.png';
  //   } else if (ext === 'txt' || ext === 'text') {
  //     return 'txt.png';
  //   }
  //   return 'file.png';
  // }

  render() {
    const { items, loading, label, hasUploadedItems, imgsOnStage } = this.state;
    const { showUploadButton, tags } = this.props;
    return (<Fragment>
      <div className="clearfix" style={{ marginBottom: '10px' }}>
        <div style={{ padding: '0 10px' }}>
          <Creatable
            multi
            options={tags.map((tag) => ({
              label: tag, value: tag
            }))}
            value={this.makeTags(label)}
            onChange={this.addTags.bind(this)}
            placeholder={ACMS.i18n("media.add_tags")}
          />
        </div>
      </div>
      <div className="acms-admin-media-upload-container-wrap">
        <div className="acms-admin-media-upload-container">
          <div className="acms-admin-media-upload-item">
            <div className="acms-admin-media-upload-item-inner">
              <DropZone onComplete={this.onComplete.bind(this)} />
            </div>
          </div>
          {items.map(item => (
            <div className="acms-admin-media-upload-item">
              <div className={classNames("acms-admin-media-upload-item-inner", {
                '-progress': item.progress >= 0
              })} style={{position: 'relative'}}>
              {item.progress >= 0 && <div style={{position: 'absolute', top: '0', left: '0', width: '100%', boxSizing: 'border-box', zIndex: '2'}}>
                <ProgressBar progress={item.progress} label={ACMS.i18n("media.sending")} alert={item.progressError} />
              </div>}
                {!item.uploadedItem && <button type="button" className="acms-admin-media-upload-cancel" onClick={this.removeFile.bind(this, item)} />}
                {item.uploadedItem && <button type="button" className="acms-admin-media-edit-btn acms-admin-media-edit-upload-btn" onClick={this.editItem.bind(this, item)}>{ACMS.i18n("media.edit")}</button>}
                {item.filetype === 'image' &&
                  <div
                    className="acms-admin-media-upload-bg"
                    style={{ backgroundImage: `url(${item.preview})` }}
                  />
                }
                {item.filetype !== 'image' &&
                  <div className="acms-admin-media-upload-bg">
                    <img
                      src={`${ACMS.Config.root}themes/system/images/fileicon/file.png`}
                      className="acms-admin-media-upload-file"
                    />
                  </div>
                }
                <div className="acms-admin-media-overlay" />
                <div className="acms-admin-media-upload-caption">
                  <p className="acms-admin-media-upload-caption-text">{item.name}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
      {items.length === 0 && <p style={{ textAlign: 'center' }}>{ACMS.i18n("media.no_staged_media")}</p>}
      {(showUploadButton && imgsOnStage) &&
        <div className="clearfix">
          <button type="button" className="acms-admin-btn-admin acms-admin-btn-admin-primary acms-admin-float-right" onClick={this.uploadItems.bind(this)} disabled={hasUploadedItems}>{ACMS.i18n("media.upload")}</button>
        </div>
      }
    </Fragment>);
  }
}
