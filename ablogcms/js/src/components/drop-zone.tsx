import React, { Component, ReactNode } from 'react'
import classNames from 'classnames'
import { ExtendedFile } from '../types/media'
import readFiles from '../lib/read-files'

interface DropZoneProp {
  children?: ReactNode
  onComplete: (files: ExtendedFile[]) => void
}

interface DropZoneState {
  files: File[]
  modifier: string
  dragging: number
  reading: boolean
}

export default class DropZone extends Component<DropZoneProp, DropZoneState> {
  constructor(props: DropZoneProp) {
    super(props)
    this.state = {
      files: [],
      modifier: 'drag-n-drop-hover',
      dragging: 0,
      reading: false,
    }
  }

  componentDidMount() {
    setTimeout(() => {
      this.setState({
        modifier: 'drag-n-drop-hover drag-n-drop-fadeout',
      })
    }, 800)
    setTimeout(() => {
      this.setState({
        modifier: '',
      })
    }, 1100)
  }

  onChange(e: React.ChangeEvent<HTMLInputElement>) {
    if (!(e.target.files instanceof FileList)) {
      return
    }
    this.setState({
      reading: true,
    })
    readFiles(e.target.files).then((files) => {
      const { onComplete } = this.props
      onComplete(files)
      this.setState({
        reading: false,
      })
    })
  }

  onDrop(e: React.DragEvent<HTMLDivElement>) {
    e.preventDefault()
    e.stopPropagation()

    this.setState({
      modifier: '',
      dragging: 0,
      reading: true,
    })
    readFiles(e.dataTransfer.files).then((files) => {
      const { onComplete } = this.props
      onComplete(files)
      this.setState({
        reading: false,
      })
    })
    return false
  }

  onDragOver(e: React.DragEvent<HTMLDivElement>) {
    e.preventDefault()
    e.stopPropagation()
    this.setState({
      modifier: 'drag-n-drop-hover',
    })
    return false
  }

  onDragEnter(e: React.DragEvent<HTMLDivElement>) {
    e.preventDefault()
    e.stopPropagation()
    const { dragging } = this.state
    this.setState({
      modifier: 'drag-n-drop-hover',
      dragging: dragging + 1,
    })
    return false
  }

  onDragLeave(e: React.DragEvent<HTMLDivElement>) {
    e.preventDefault()
    e.stopPropagation()
    let { dragging } = this.state
    dragging--
    if (dragging === 0) {
      this.setState({
        modifier: '',
      })
    }
    this.setState({
      dragging,
    })
    return false
  }

  render() {
    const { children } = this.props
    const { modifier, reading } = this.state
    return (
      <div
        className={classNames('acms-admin-media-drop-box-wrap', modifier)}
        onDragOver={this.onDragOver.bind(this)}
        onDragEnter={this.onDragEnter.bind(this)}
        onDragLeave={this.onDragLeave.bind(this)}
        onDrop={this.onDrop.bind(this)}
      >
        {children || (
          <div
            className={classNames(
              'acms-admin-media',
              'acms-admin-media-drop-box',
              modifier,
            )}
          >
            <div className="acms-admin-media-drop-inside">
              <p className="acms-admin-media-drop-text">
                {ACMS.i18n('media.add_new_media')}
              </p>
              {/* eslint-disable-next-line jsx-a11y/label-has-associated-control */}
              <label
                className="acms-admin-media-unit-droparea-btn"
                style={{ cursor: 'pointer' }}
              >
                <input
                  type="file"
                  multiple
                  name="files[]"
                  onChange={this.onChange.bind(this)}
                  style={{ display: 'none' }}
                  disabled={reading === true}
                />
                {ACMS.i18n('media.upload')}
              </label>
              <p className="acms-admin-media-drop-text">
                {ACMS.i18n('media.drop_file')}
              </p>
            </div>
          </div>
        )}
      </div>
    )
  }
}
