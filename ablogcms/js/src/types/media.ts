import * as actions from '../actions/media';

export type MediaItem = {
  media_title: string,
  media_thumbnail: string,
  media_label: string,
  media_last_modified: string,
  media_datetime: string,
  media_id: string,
  media_bid: string,
  media_blog_name: string,
  media_size: string,
  media_filesize: number,
  media_path: string,
  media_pdf: string,
  media_original: string,
  media_edited: string,
  media_permalink: string,
  media_type: string,
  media_ext: string,
  media_caption: string,
  media_link: string,
  media_alt: string,
  media_text: string,
  media_focal_point: string,
  media_landscape?: 'horizontal' | 'vertical',
  checked: boolean,
  width?: number,
  height?: number
};

export interface MediaAjaxConfig {
  page?: number,
  tag?: string,
  keyword?: string,
  order?: string,
  limit?: string,
  date?: string,
  filetype?: string,
  fileext?: string
}

export interface MediaContainerProp {
  items: MediaItem[],
  tags: string[],
  extensions: string[],
  actions: typeof actions,
  item: MediaItem,
  largeSize: number,
  formToken: string,
  archives: string[],
  upload: boolean,
  label: string,
  mode: string,
  lastPage: number,
  total: number,
  config: MediaAjaxConfig,
  loading: boolean,
  tab: 'upload'|'select'
}

export interface MediaInsertContainerProp extends MediaContainerProp {
  onInsert: Function,
  onClose: Function
}

export interface MediaTag {
  value: string,
  label: string
}

export interface ExtendedFile {
  file: File,
  filetype: string,
  preview?: string
}

