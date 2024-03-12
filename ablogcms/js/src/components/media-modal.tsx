import React, { Component } from 'react'
import ReactDOM from 'react-dom'
import * as DOMPurify from 'dompurify'
import { CopyToClipboard } from 'react-copy-to-clipboard'
import { Creatable } from './react-select-styled'
import ResizeImage from '../lib/resize-image/util'
import { formatBytes, setTooltips, dataURItoBlob } from '../lib/utility'

import Splash from './splash'
import Notify from './notify'
import MediaEditModal from './media-edit-modal'
import { setItem, updateMediaList } from '../actions/media'
import { MediaItem } from '../types/media'

/* eslint jsx-a11y/click-events-have-key-events: 0 */
/* eslint jsx-a11y/no-static-element-interactions: 0 */

/* eslint jsx-a11y/click-events-have-key-events: 0 */
/* eslint jsx-a11y/no-static-element-interactions: 0 */

const delimiter = ','
const resizeImage = new ResizeImage()

type MediaModalState = {
  loading: boolean
  open: boolean
  copied: boolean
  blob: Blob | null
  file: File | null
  preview: string | null
  previewWidth?: number
  previewHeight?: number
  pdfPreviewed: boolean
  pdfHasPrevPage: boolean
  pdfHasNextPage: boolean
  focalPoint?: [number, number] | []
  replaced: boolean // check if the image is updated via input type="file"
}

type MediaModalProp = {
  formToken: string
  item: MediaItem
  tags?: string[]
  onClose?: () => void
  onUpdate?: () => void
  actions: {
    setItem: typeof setItem
    updateMediaList: typeof updateMediaList
  }
}

export default class MediaModal extends Component<
  MediaModalProp,
  MediaModalState
> {
  form: HTMLFormElement

  modal: HTMLElement | null

  root: HTMLElement

  pdf2Image: any // eslint-disable-line @typescript-eslint/no-explicit-any

  static defaultProps = {
    tags: [],
  }

  constructor(props) {
    super(props)
    this.state = {
      loading: false,
      open: false,
      file: null,
      blob: null,
      preview: null,
      copied: false,
      replaced: false,
      pdfPreviewed: false,
      pdfHasPrevPage: false,
      pdfHasNextPage: false,
      focalPoint: [],
    }
    this.root = document.createElement('div')
    this.pdf2Image = null
    document.body.appendChild(this.root)
  }

  async componentDidMount() {
    const { item } = this.props
    if (item.media_ext.toLowerCase() === 'pdf') {
      this.pdfPreview(parseInt(item.media_pdf_page, 10) || 1)
    } else {
      this.getPreviewSize(item.media_thumbnail)
      setTooltips(this.modal)
    }
  }

  componentWillUnmount() {
    document.body.removeChild(this.root)
  }

  async setPdfPreview(image) {
    const { item } = this.props
    item.media_thumbnail = image
    const hasPrevPage = await this.pdf2Image.hasPrevPage()
    const hasNextPage = await this.pdf2Image.hasNextPage()
    this.setState({
      pdfHasPrevPage: hasPrevPage,
      pdfHasNextPage: hasNextPage,
      pdfPreviewed: true,
    })
  }

  pdfPreview(page) {
    const { item } = this.props
    import(/* webpackChunkName: "pdf2image" */ '../lib/pdf2image').then(
      async ({ default: Pdf2Image }) => {
        this.pdf2Image = new Pdf2Image(item.media_permalink)
        const image = await this.pdf2Image.getPageImage(page)
        await this.setPdfPreview(image)
        this.getPreviewSize(item.media_thumbnail)
        setTooltips(this.modal)
      },
    )
  }

  pdfPrevPage(e) {
    e.preventDefault()
    this.pdf2Image.getPrevImage().then(async (image) => {
      this.setPdfPreview(image)
    })
  }

  pdfNextPage(e) {
    e.preventDefault()
    this.pdf2Image.getNextImage().then(async (image) => {
      this.setPdfPreview(image)
    })
  }

  getPreviewSize(preview: string) {
    const img = new Image()
    img.onload = () => {
      this.setState({
        previewWidth: img.width,
        previewHeight: img.height,
      })
    }
    img.src = preview
  }

  hideModal() {
    this.props.actions.setItem(null)
    // 再度開いたときにメディアが開かないようにモーダルのURLを削除
    if ('history' in window) {
      history.replaceState(null, null, '#')
    }
    if (this.props.onClose) {
      this.props.onClose()
    }
  }

  buildFormData(postName: string) {
    const { blob, file, focalPoint, replaced, pdfPreviewed } = this.state
    const { item } = this.props
    const formData = new FormData(this.form)
    if (blob) {
      formData.append('media_file', blob)
    } else if (file) {
      formData.append('media_file', file)
    }
    if (focalPoint.length) {
      formData.append('media[]', 'focal_x')
      formData.append('media[]', 'focal_y')
      formData.append('focal_x', `${focalPoint[0]}`)
      formData.append('focal_y', `${focalPoint[1]}`)
    }
    if (replaced) {
      formData.append('media[]', 'replaced')
      formData.append('replaced', 'true')
    }
    if (item.media_ext.toLowerCase() === 'pdf' && pdfPreviewed) {
      formData.append(
        'media_pdf_thumbnail',
        dataURItoBlob(item.media_thumbnail),
      )

      formData.append('media[]', 'pdf_page')
      formData.append('pdf_page', this.pdf2Image.currentPage)
    }
    formData.append(postName, 'true')
    formData.append('formToken', window.csrfToken)
    return formData
  }

  upload() {
    const { actions, item } = this.props
    const { file, blob } = this.state
    const formData = this.buildFormData('ACMS_POST_Media_Update')
    if (file || blob) {
      const msg = file
        ? ACMS.i18n('media.file_changed')
        : ACMS.i18n('media.img_changed')
      if (!confirm(msg)) {
        return
      }
    }
    this.setState({
      loading: true,
      file: null,
      blob: null,
    })
    const url = ACMS.Library.acmsLink({
      Query: {
        _mid: item.media_id,
      },
    })
    $.ajax({
      url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).then((res) => {
      this.setState({
        loading: false,
      })
      if (res.status === 'failure') {
        if (res.message) {
          alert(res.message)
        } else {
          alert(ACMS.i18n('media.cannot_edit'))
        }
        return
      }
      actions.updateMediaList(res)
      actions.setItem(null)
      if (this.props.onUpdate) {
        this.props.onUpdate(res)
      }
    })
  }

  uploadAsNew() {
    const { actions, item } = this.props
    const formData = this.buildFormData('ACMS_POST_Media_UpdateAsNew')
    this.setState({
      loading: true,
      file: null,
      blob: null,
    })
    const url = ACMS.Library.acmsLink({
      Query: {
        _mid: item.media_id,
      },
    })
    $.ajax({
      url,
      type: 'POST',
      data: formData,
      processData: false,
      contentType: false,
    }).then((res) => {
      this.setState({
        loading: false,
      })
      if (res.status === 'failure') {
        if (res.message) {
          alert(res.message)
        } else {
          alert(ACMS.i18n('media.cannot_edit'))
        }
        return
      }
      actions.fetchMediaList()
      actions.setItem(null)
      if (this.props.onUpdate) {
        this.props.onUpdate(res)
      }
    })
  }

  onInput(e, prop) {
    const { actions, item } = this.props
    actions.setItem({ ...item, [prop]: e.target.value })
  }

  makeTags(label) {
    if (!label) {
      return null
    }
    const labels = label.split(delimiter)
    return labels.map((label) => ({
      value: label,
      label,
    }))
  }

  addTags(tags) {
    const { actions, item } = this.props
    const label = tags.reduce((val, tag, idx) => {
      if (idx === 0) {
        return tag.value
      }
      return `${val}${delimiter}${tag.value}`
    }, '')

    actions.setItem({ ...item, media_label: label })
  }

  openEditDialog() {
    this.setState({
      open: true,
    })
  }

  blobToDataURL(blob: Blob): Promise<string> {
    return new Promise((resolve) => {
      const fr = new FileReader()
      fr.onload = (e) => {
        resolve(e.target.result)
      }
      fr.readAsDataURL(blob)
    })
  }

  onCloseEditDialog(blob: Blob, focalPoint: [number, number]) {
    if (blob) {
      this.blobToDataURL(blob).then((preview: string) => {
        if (focalPoint) {
          this.setState({
            preview,
            blob,
            focalPoint,
          })
        } else {
          this.setState({
            preview,
            blob,
          })
        }
      })
    }
    this.setState({
      open: false,
    })
  }

  createPdfThumbnail(file) {
    return new Promise((resolve) => {
      if (!file) {
        resolve()
      }
      const reader = new FileReader()
      reader.onload = (event) => {
        import(/* webpackChunkName: "pdf2image" */ '../lib/pdf2image').then(
          async ({ default: Pdf2Image }) => {
            this.pdf2Image = new Pdf2Image(new Uint8Array(event.target.result))
            this.pdf2Image.currentPage = 1
            this.pdf2Image.getPageImage(1).then((image) => {
              resolve(image)
            })
          },
        )
      }
      reader.readAsArrayBuffer(file)
    })
  }

  async changeImage(e) {
    const { item } = this.props
    const file: File = e.target.files[0]
    // eslint-disable-next-line no-nested-ternary
    const type = file.type.match(/svg/)
      ? 'svg'
      : file.type.match(/image/)
      ? 'image'
      : 'file'
    if (item.media_type !== type) {
      alert(ACMS.i18n('media.cannnot_change_type'))
    }
    const nextState = {
      replaced: true,
    }
    if (type === 'image') {
      const [resizeType, largeSize] = ACMS.Config.lgImg.split(':')
      const { blob, resize } = await resizeImage.getBlobFromFile(
        file,
        resizeType,
        parseInt(largeSize, 10),
      )
      const preview = await this.blobToDataURL(blob)
      this.getPreviewSize(preview)
      nextState.preview = preview
      if (resize === false || ACMS.Config.mediaClientResize !== 'on') {
        nextState.blob = file
      } else {
        nextState.blob = blob
      }
    } else if (type === 'svg') {
      const textData = await file.text()
      const clean = DOMPurify.sanitize(textData, {
        USE_PROFILES: { svg: true, svgFilters: true },
      })
      const reBlob = new Blob([clean], { type: 'image/svg+xml' })
      nextState.file = new File([reBlob], file.name, { type: reBlob.type })
    } else if (type === 'file') {
      nextState.file = file
      this.createPdfThumbnail(file).then((image) => {
        item.media_thumbnail = image
      })
    }
    item.media_title = file.name
    this.setState(nextState)
  }

  getImgWidth(size: string) {
    const [width] = size.split(' x ')
    return `${width}px`
  }

  render() {
    const { item, formToken, tags } = this.props
    const { loading, open, preview, copied, pdfHasPrevPage, pdfHasNextPage } =
      this.state
    return ReactDOM.createPortal(
      <>
        <div
          className="acms-admin-modal in"
          style={{ display: 'block', backgroundColor: 'rgba(0,0,0,.5)' }}
          ref={(modal) => {
            this.modal = modal
          }}
        >
          <div className="acms-admin-modal-dialog medium">
            <div className="acms-admin-modal-content">
              <div className="acms-admin-modal-header">
                <i
                  className="acms-admin-modal-hide acms-admin-icon-delete"
                  onClick={this.hideModal.bind(this)}
                />
                <h3 className="acms-admin-modal-heading">
                  {ACMS.i18n('media.media_edit')}
                </h3>
              </div>
              <div className="acms-admin-modal-body">
                <div className="acms-admin-padding-small">
                  <form
                    className="acms-admin-form"
                    ref={(form: HTMLFormElement) => {
                      this.form = form
                    }}
                  >
                    <div className="acms-admin-grid">
                      <div className="acms-admin-col-md-6">
                        {item.media_type === 'image' && (
                          <div className="acms-admin-media-thumb-container acms-admin-margin-bottom-small">
                            {item.media_ext !== 'svg' && (
                              <button
                                className="acms-admin-media-edit-btn acms-admin-media-edit-thumb-btn"
                                type="button"
                                onClick={this.openEditDialog.bind(this)}
                              >
                                {ACMS.i18n('media.edit')}
                              </button>
                            )}
                            {preview ? (
                              <div
                                style={{ backgroundImage: `url("${preview}")` }}
                                className="acms-admin-media-thumb"
                              />
                            ) : (
                              <div
                                style={{
                                  backgroundImage: `url("${item.media_edited}?date=${item.media_datetime}")`,
                                  maxWidth: this.getImgWidth(item.media_size),
                                  margin: '0 auto',
                                }}
                                className="acms-admin-media-thumb"
                              />
                            )}
                          </div>
                        )}
                        {item.media_type === 'svg' && (
                          <div className="acms-admin-media-thumb-container acms-admin-margin-bottom-small acms-admin-media-thumb-container-assets">
                            <div
                              className="acms-admin-media-thumb-wrap"
                              style={{ width: 'auto' }}
                            >
                              <img
                                src={item.media_thumbnail}
                                style={{
                                  margin: 'auto',
                                  display: 'block',
                                  width: '80%',
                                }}
                                alt=""
                              />
                            </div>
                            <p className="acms-admin-media-thumb-ext">
                              {item.media_title}
                            </p>
                          </div>
                        )}
                        {item.media_ext.toLowerCase() === 'pdf' && (
                          <div className="acms-admin-media-thumb-container acms-admin-margin-bottom-small acms-admin-media-thumb-container-assets">
                            <div
                              className="acms-admin-media-thumb-wrap"
                              style={{ width: 'auto' }}
                            >
                              <img
                                src={item.media_thumbnail}
                                style={{
                                  margin: 'auto',
                                  display: 'block',
                                  width: '70%',
                                }}
                                alt=""
                              />
                            </div>
                            <div
                              style={{ marginTop: '5px' }}
                              className="acms-admin-media-thumb-pager"
                            >
                              {pdfHasPrevPage && (
                                <button
                                  type="button"
                                  className="acms-admin-media-thumb-pager-arrow acms-admin-media-thumb-pager-arrow-left"
                                  onClick={this.pdfPrevPage.bind(this)}
                                >
                                  <span className="acms-admin-icon-arrow-small-left" />
                                  <span className="acms-admin-hide-visually">
                                    前ページ
                                  </span>
                                </button>
                              )}
                              {pdfHasNextPage && (
                                <button
                                  type="button"
                                  className="acms-admin-media-thumb-pager-arrow acms-admin-media-thumb-pager-arrow-right"
                                  onClick={this.pdfNextPage.bind(this)}
                                >
                                  <span className="acms-admin-icon-arrow-small-right" />
                                  <span className="acms-admin-hide-visually">
                                    次ページ
                                  </span>
                                </button>
                              )}
                            </div>
                            <p className="acms-admin-media-thumb-ext">
                              {item.media_title}
                            </p>
                          </div>
                        )}
                        {item.media_type === 'file' &&
                          item.media_ext.toLowerCase() !== 'pdf' && (
                            <div className="acms-admin-media-thumb-container acms-admin-margin-bottom-small acms-admin-media-thumb-container-assets">
                              <div className="acms-admin-media-thumb-wrap">
                                <img
                                  src={item.media_thumbnail}
                                  style={{
                                    margin: 'auto',
                                    display: 'block',
                                    width: '70px',
                                  }}
                                  alt=""
                                />
                              </div>
                              <p className="acms-admin-media-thumb-ext">
                                {item.media_title}
                              </p>
                            </div>
                          )}
                        <div className="acms-admin-table-admin-media-edit-wrap">
                          <table className="acms-admin-table-admin-media-edit">
                            <tbody>
                              <tr>
                                <th>{ACMS.i18n('media.media_change')}</th>
                                <td>
                                  <input
                                    type="file"
                                    id="change_image"
                                    style={{
                                      display: 'none',
                                    }}
                                    onChange={this.changeImage.bind(this)}
                                  />
                                  <label
                                    htmlFor="change_image"
                                    style={{ cursor: 'pointer' }}
                                    className="acms-admin-btn-admin"
                                  >
                                    <i className="acms-admin-icon-config_export" />
                                    {ACMS.i18n('media.file_select')}
                                  </label>
                                </td>
                              </tr>
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.media_id')}
                                </th>
                                <td>{item.media_id}</td>
                              </tr>
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.upload_date')}
                                </th>
                                <td>
                                  {item.media_datetime}
                                  <input
                                    type="hidden"
                                    name="upload_date"
                                    value={item.media_datetime}
                                  />
                                  <input
                                    type="hidden"
                                    name="media[]"
                                    value="upload_date"
                                  />
                                </td>
                              </tr>
                              {item.media_type === 'image' && (
                                <tr>
                                  <th className="acms-admin-table-nowrap">
                                    {ACMS.i18n('media.image_size')}
                                  </th>
                                  <td>{item.media_size} px</td>
                                </tr>
                              )}
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.file_size')}
                                </th>
                                <td>{formatBytes(item.media_filesize)}</td>
                              </tr>
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.file_name')}
                                </th>
                                <td>{item.media_title}</td>
                              </tr>
                              {item.media_type === 'file' && (
                                <tr>
                                  <th className="acms-admin-table-nowrap">
                                    {ACMS.i18n('media.intrinsic_link')}
                                  </th>
                                  <td>
                                    <div className="acms-admin-form-action">
                                      <input
                                        type="text"
                                        value={item.media_path}
                                        readOnly
                                        className="acms-admin-form-width-full"
                                      />
                                      <CopyToClipboard
                                        text={item.media_path}
                                        onCopy={() => {
                                          this.setState({
                                            copied: true,
                                          })
                                        }}
                                      >
                                        <span className="acms-admin-form-side-btn">
                                          <button
                                            type="button"
                                            className="acms-admin-btn"
                                          >
                                            {ACMS.i18n('media.copy')}
                                          </button>
                                        </span>
                                      </CopyToClipboard>
                                    </div>
                                    <p
                                      style={{
                                        fontSize: '12px',
                                        marginTop: '5px',
                                      }}
                                      className="acms-admin-text-primary"
                                    >
                                      {ACMS.i18n(
                                        'media.intrinsic_link_attention',
                                      )}
                                    </p>
                                  </td>
                                </tr>
                              )}
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.permalink')}
                                </th>
                                <td>
                                  <div className="acms-admin-form-action">
                                    <input
                                      type="text"
                                      value={item.media_permalink}
                                      readOnly
                                      className="acms-admin-form-width-full"
                                    />
                                    <CopyToClipboard
                                      text={item.media_permalink}
                                      onCopy={() => {
                                        this.setState({
                                          copied: true,
                                        })
                                      }}
                                    >
                                      <span className="acms-admin-form-side-btn">
                                        <button
                                          type="button"
                                          className="acms-admin-btn"
                                        >
                                          {ACMS.i18n('media.copy')}
                                        </button>
                                      </span>
                                    </CopyToClipboard>
                                  </div>
                                  {item.media_type !== 'file' && (
                                    <p
                                      style={{
                                        fontSize: '12px',
                                        marginTop: '5px',
                                      }}
                                      className="acms-admin-text-danger"
                                    >
                                      {ACMS.i18n('media.copy_attention')}
                                    </p>
                                  )}
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                      <div className="acms-admin-col-md-6">
                        <div className="acms-admin-table-admin-media-edit-wrap">
                          <table className="acms-admin-table-admin-media-edit">
                            <tbody>
                              {item.media_type === 'file' && (
                                <tr>
                                  <th className="acms-admin-table-nowrap">
                                    {ACMS.i18n('media.status')}
                                    <i
                                      className="acms-admin-icon-tooltip js-acms-tooltip-hover"
                                      data-acms-position="top"
                                      data-acms-tooltip={ACMS.i18n(
                                        'media.status_settings',
                                      )}
                                    />
                                  </th>
                                  <td>
                                    <select
                                      name="status"
                                      value={item.media_status}
                                      onChange={(e) => {
                                        this.onInput(e, 'media_status')
                                      }}
                                    >
                                      <option value="entry">
                                        {ACMS.i18n('media.follow_media_status')}
                                      </option>
                                      <option value="open">
                                        {ACMS.i18n('media.open')}
                                      </option>
                                      <option value="close">
                                        {ACMS.i18n('media.close')}
                                      </option>
                                      <option value="secret">
                                        {ACMS.i18n('media.secret')}
                                      </option>
                                    </select>
                                    <input
                                      type="hidden"
                                      name="media[]"
                                      value="status"
                                    />
                                  </td>
                                </tr>
                              )}
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.tag')}
                                  <i
                                    className="acms-admin-icon-tooltip acms-admin-margin-left-mini js-acms-tooltip-hover"
                                    data-acms-position="top"
                                    data-acms-tooltip={ACMS.i18n(
                                      'media.managed_tag',
                                    )}
                                  />
                                </th>
                                <td>
                                  <Creatable
                                    multi
                                    value={this.makeTags(item.media_label)}
                                    className="acms-admin-form-width-full acms-admin-select2"
                                    onChange={this.addTags.bind(this)}
                                    options={tags.map((tag) => ({
                                      label: tag,
                                      value: tag,
                                    }))}
                                  />
                                  <input
                                    type="hidden"
                                    value={item.media_label}
                                    name="media_label"
                                  />
                                  <input
                                    type="hidden"
                                    name="media[]"
                                    value="media_label"
                                  />
                                </td>
                              </tr>
                              <tr
                                style={{
                                  display:
                                    typeof ACMS !== 'undefined' &&
                                    ACMS.Config &&
                                    ACMS.Config.mediaShowAltAndCaptionOnModal
                                      ? 'table-row'
                                      : 'none',
                                }}
                              >
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.filename')}
                                  <i
                                    className="acms-admin-icon-tooltip acms-admin-margin-left-mini js-acms-tooltip-hover"
                                    data-acms-position="top"
                                    data-acms-tooltip={ACMS.i18n(
                                      'media.filename_settings',
                                    )}
                                  />
                                </th>
                                <td>
                                  <input
                                    type="text"
                                    value={item.media_title}
                                    name="rename"
                                    className="acms-admin-form-width-full"
                                    onInput={(e) => {
                                      this.onInput(e, 'media_title')
                                    }}
                                  />
                                  <input
                                    type="hidden"
                                    name="media[]"
                                    value="rename"
                                  />
                                </td>
                              </tr>
                              <tr
                                style={{
                                  display:
                                    typeof ACMS !== 'undefined' &&
                                    ACMS.Config &&
                                    ACMS.Config.mediaShowAltAndCaptionOnModal
                                      ? 'table-row'
                                      : 'none',
                                }}
                              >
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.caption')}
                                  <i
                                    className="acms-admin-icon-tooltip acms-admin-margin-left-mini js-acms-tooltip-hover"
                                    data-acms-position="top"
                                    data-acms-tooltip={ACMS.i18n(
                                      'media.caption_settings',
                                    )}
                                  />
                                </th>
                                <td>
                                  <input
                                    type="text"
                                    value={item.media_caption}
                                    name="field_1"
                                    className="acms-admin-form-width-full"
                                    onInput={(e) => {
                                      this.onInput(e, 'media_caption')
                                    }}
                                  />
                                  <input
                                    type="hidden"
                                    name="media[]"
                                    value="field_1"
                                  />
                                </td>
                              </tr>
                              <tr
                                style={{
                                  display:
                                    typeof ACMS !== 'undefined' &&
                                    ACMS.Config &&
                                    ACMS.Config.mediaShowAltAndCaptionOnModal
                                      ? 'table-row'
                                      : 'none',
                                }}
                              >
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.alt')}
                                  <i
                                    className="acms-admin-icon-tooltip acms-admin-margin-left-mini js-acms-tooltip-hover"
                                    data-acms-position="top"
                                    data-acms-tooltip={ACMS.i18n(
                                      'media.alt_settings',
                                    )}
                                  />
                                </th>
                                <td>
                                  <input
                                    type="text"
                                    value={item.media_alt}
                                    name="field_3"
                                    className="acms-admin-form-width-full"
                                    onInput={(e) => {
                                      this.onInput(e, 'media_alt')
                                    }}
                                  />
                                  <input
                                    type="hidden"
                                    name="media[]"
                                    value="field_3"
                                  />
                                </td>
                              </tr>
                              {item.media_type !== 'file' && (
                                <tr>
                                  <th className="acms-admin-table-nowrap">
                                    {ACMS.i18n('media.link')}
                                    <i
                                      className="acms-admin-icon-tooltip acms-admin-margin-left-mini js-acms-tooltip-hover"
                                      data-acms-position="top"
                                      data-acms-tooltip={ACMS.i18n(
                                        'media.link_settings',
                                      )}
                                    />
                                  </th>
                                  <td>
                                    <input
                                      type="text"
                                      value={item.media_link}
                                      name="field_2"
                                      className="acms-admin-form-width-full"
                                      onInput={(e) => {
                                        this.onInput(e, 'media_link')
                                      }}
                                    />
                                    <input
                                      type="hidden"
                                      name="media[]"
                                      value="field_2"
                                    />
                                  </td>
                                </tr>
                              )}
                              <tr>
                                <th className="acms-admin-table-nowrap">
                                  {ACMS.i18n('media.details')}
                                  <i
                                    className="acms-admin-icon-tooltip acms-admin-margin-left-mini js-acms-tooltip-hover"
                                    data-acms-position="top"
                                    data-acms-tooltip={ACMS.i18n(
                                      'media.details_settings',
                                    )}
                                  />
                                </th>
                                <td>
                                  <textarea
                                    value={item.media_text}
                                    name="field_4"
                                    className="acms-admin-form-width-full"
                                    onInput={(e) => {
                                      this.onInput(e, 'media_text')
                                    }}
                                  />
                                  <input
                                    type="hidden"
                                    name="media[]"
                                    value="field_4"
                                  />
                                </td>
                              </tr>
                            </tbody>
                          </table>
                        </div>
                      </div>
                    </div>
                    <input type="hidden" name="formToken" value={formToken} />
                    {item.media_ext && (
                      <input
                        type="hidden"
                        name="extension"
                        value={item.media_ext.toUpperCase()}
                      />
                    )}
                    <input type="hidden" name="type" value={item.media_type} />
                    <input type="hidden" name="media[]" value="extension" />
                    <input type="hidden" name="media[]" value="type" />
                    <input
                      type="hidden"
                      name="file_size"
                      value={item.media_size}
                    />
                    <input type="hidden" name="media[]" value="file_size" />
                    <input type="hidden" name="path" value={item.media_path} />
                    <input type="hidden" name="media[]" value="path" />
                    <input
                      type="hidden"
                      name="file_name"
                      value={item.media_title}
                    />
                    <input type="hidden" name="media[]" value="file_name" />
                  </form>
                </div>
                <div className="acms-admin-modal-footer acms-admin-media-modal-footer">
                  <button
                    type="button"
                    className="acms-admin-btn acms-admin-margin-top-mini acms-admin-media-cancel-btn"
                    onClick={this.hideModal.bind(this)}
                  >
                    {ACMS.i18n('media.cancel')}
                  </button>
                  <button
                    type="button"
                    className="acms-admin-btn acms-admin-btn-primary acms-admin-margin-top-mini"
                    onClick={this.uploadAsNew.bind(this)}
                  >
                    {ACMS.i18n('media.save_as_new')}
                  </button>
                  <button
                    type="button"
                    className="acms-admin-btn acms-admin-btn-primary acms-admin-margin-top-mini"
                    onClick={this.upload.bind(this)}
                  >
                    {ACMS.i18n('media.save')}
                  </button>
                </div>
              </div>
            </div>
          </div>
          {loading && <Splash message={ACMS.i18n('media.saving')} />}
        </div>
        {open && (
          <MediaEditModal
            onClose={this.onCloseEditDialog.bind(this)}
            src={preview || item.media_edited}
            original={item.media_original}
            focalPoint={item.media_focal_point}
            size={item.media_size}
            mediaCropSizes={ACMS.Config.mediaCropSizes}
          />
        )}
        <Notify
          message={ACMS.i18n('media.permalink_copied')}
          show={copied}
          onFinish={() => {
            this.setState({
              copied: false,
            })
          }}
        />
      </>,
      this.root,
    )
  }
}
