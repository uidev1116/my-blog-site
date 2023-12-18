import React from 'react';
import { render, unmountComponentAtNode } from 'react-dom';

import MediaInsert from '../components/media-insert';
import MediaUpdate from '../components/media-update';
import MediaDropArea from '../components/media-droparea';
import { MediaItem } from '../types/media';
import { random } from '../lib/utility';
import { remove } from '../lib/dom';

const insertMark = '.js-insert';
const editMark = '.js-edit';
const removeMark = '.js-remove';
const previewMark = '.js-preview';
const valueMark = '.js-value';
const dropAreaMark = '.js-droparea';
const errorTextMark = '.js-text';
const dispatch = (ctx) => {
  const $target = $(valueMark, ctx);
  const $remove = $(removeMark, ctx);
  const $preview = $(previewMark, ctx);
  const $insert = $(insertMark, ctx);
  const $edit = $(editMark, ctx);
  const $droparea = $(dropAreaMark, ctx);
  const $error = $(errorTextMark, ctx);
  if (!$target.val()) {
    $edit.hide();
    $remove.hide();
  }

  const renderDropArea = ({
    type, thumbnail, mid, caption, width, height, thumbnailType,
  }) => {
    unmountComponentAtNode($droparea.get(0));
    render(
      <MediaDropArea
        caption={caption}
        thumbnail={thumbnail}
        mediaThumbnailType={thumbnailType}
        mediaType={type}
        width={width}
        height={height}
        mid={mid}
        onChange={(nextMid) => {
          $target.val(nextMid);
          if ($error.length) {
            $error.hide();
          }
        }}
        onError={() => {
          if ($error.length) {
            $error.show();
          }
        }}
      />,
      $droparea.get(0),
    );
  };
  if ($droparea.length) {
    const thumbnail = $droparea.data('thumbnail');
    const type = $droparea.data('type');
    const mid = $target.val();
    const caption = $droparea.data('caption');
    const width = $droparea.data('width');
    const height = $droparea.data('height');
    const thumbnailType = $droparea.data('thumbnail-type');
    renderDropArea({
      thumbnail,
      type,
      mid,
      caption,
      width,
      height,
      thumbnailType,
    });
  }
  // メディア挿入
  $insert.off('click').on('click', (e) => {
    e.preventDefault();
    const id = `media-${random(10)}`;
    $('body').append(`<div id="${id}"></div>`);
    const mediaTarget = document.querySelector(`#${id}`);
    const tab = $(e.target).data('mode');
    const filetype = $(e.target).data('type') ? $(e.target).data('type') : 'all';
    render(
      <MediaInsert
        tab={tab}
        radioMode
        filetype={filetype}
        onInsert={(items) => {
          if (!items || !items.length) {
            alert('メディアが選択されていません。');
            return;
          }
          const [item] = items;
          if ($target.length) {
            $target.val(item.media_id);
          }
          if ($preview.length) {
            $preview.attr('src', item.media_thumbnail);
            $preview.show();
          }
          if ($droparea.length) {
            const thumbnail = item.media_thumbnail;
            const type = item.media_type;
            const mid = item.media_id;
            const caption = $droparea.data('caption');
            const width = $droparea.data('width');
            const height = $droparea.data('height');
            renderDropArea({
              thumbnail,
              type,
              mid,
              caption,
              width,
              height,
            });
          }
          $edit.show();
          $remove.show();
          $error.hide();
          unmountComponentAtNode(mediaTarget);
          remove(mediaTarget);
        }}
        onClose={() => {
          unmountComponentAtNode(mediaTarget);
          remove(mediaTarget);
        }}
      />,
      mediaTarget,
    );
  });
  // メディア編集
  $edit.off('click').on('click', (e) => {
    e.preventDefault();
    const id = `media-${random(10)}`;
    $('body').append(`<div id="${id}"></div>`);
    const mid = $target.val();
    const mediaTarget = document.querySelector(`#${id}`);
    render(
      <MediaUpdate
        mid={mid}
        onClose={() => {
          unmountComponentAtNode(mediaTarget);
          remove(mediaTarget);
        }}
        onUpdate={(media: MediaItem) => {
          unmountComponentAtNode(mediaTarget);
          remove(mediaTarget);
          if ($preview.length) {
            $preview.attr('src', media.media_thumbnail);
            $target.val(media.media_id);
          }
        }}
      />,
      mediaTarget,
    );
  });
  // メディア削除
  $remove.off('click').on('click', (e) => {
    e.preventDefault();
    $target.val('');
    $edit.hide();
    $remove.hide();
    $preview.attr('src', '').hide();
  });
};

export default (ctx: HTMLElement) => {
  const fields = ctx.querySelectorAll(ACMS.Config.mediaFieldMark);
  const active = ACMS.Config.mediaLibrary !== 'off';
  [].forEach.call(fields, (field) => {
    if (active) {
      dispatch(field);
    } else {
      field.innerHTML = `<span style="color: red;">&nbsp;&nbsp;機能を利用するには<a href="${ACMS.Config.root}bid/${ACMS.Config.bid}/admin/config_function/">機能設定</a>にてメディア管理を利用可能にしてください</span>`;
    }
  });
};
